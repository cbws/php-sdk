<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\ExternalAccount\Config;
use Cbws\Sdk\Auth\ExternalAccount\Exception\StsException;
use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;
use Cbws\Sdk\Auth\ExternalAccount\SupplierOptions;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Iam\Client as IamClient;
use Closure;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Access tokens for a workload outside CBWS, exchanged at STS for its subject token and, where the configuration names
 * a service account, turned into that account's token by IAM.
 *
 * The subject token is read again for every exchange, because the tokens of CI jobs and projected Kubernetes service
 * accounts rotate. With impersonation, the STS token is used once for the IAM call and not kept. The returned token is
 * reused until REFRESH_MARGIN before it expires. No exception carries the subject token or a token this returns.
 */
class ExternalAccountTokenSource implements TokenSourceContract
{
    /**
     * How long before its expiry a token is replaced, in seconds, so that it outlives the call it is fetched for.
     */
    public const REFRESH_MARGIN = 60;

    private const GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:token-exchange';

    private const REQUESTED_TOKEN_TYPE = 'urn:ietf:params:oauth:token-type:access_token';

    protected ?AccessTokenInterface $token = null;

    protected ClientInterface $httpClient;

    /**
     * @var Closure(non-empty-string, Credentials): IamClient
     */
    protected Closure $iamClientFactory;

    /**
     * @param string[]                                                 $scopes           the scopes of an impersonated
     *                                                                                   account's token; the STS
     *                                                                                   token has none
     * @param ClientInterface|null                                     $httpClient       the HTTP client for STS
     * @param (Closure(non-empty-string, Credentials): IamClient)|null $iamClientFactory builds the IAM client of the
     *                                                                                   impersonation step, for the
     *                                                                                   project of the account and
     *                                                                                   with the STS token, such as
     *                                                                                   one that reaches a stand-in in
     *                                                                                   a test or a lab
     */
    public function __construct(
        protected Config $config,
        protected array $scopes = [],
        ?ClientInterface $httpClient = null,
        ?Closure $iamClientFactory = null,
    ) {
        $this->httpClient = $httpClient ?? new HttpClient(['connect_timeout' => 5, 'timeout' => 30]);

        $endpoint = $config->iamEndpoint;

        // The Go SDK takes a gRPC target, so a configuration shared with it may name one.
        if ($endpoint !== null && str_starts_with($endpoint, 'dns:///')) {
            $endpoint = substr($endpoint, strlen('dns:///'));
        }

        $this->iamClientFactory = $iamClientFactory
            ?? static fn (string $project, Credentials $credentials): IamClient => (new IamClient($project, $credentials))->withEndpoint($endpoint);
    }

    /**
     * @throws StatusException|StsException|SubjectTokenException
     */
    public function token(): AccessTokenInterface
    {
        if ($this->token !== null && !$this->due($this->token)) {
            return $this->token;
        }

        return $this->token = $this->fetch();
    }

    public function getProject(): ?string
    {
        return $this->config->getProject();
    }

    /**
     * @throws StatusException|StsException|SubjectTokenException
     */
    protected function fetch(): AccessTokenInterface
    {
        $federated = $this->exchange();
        $serviceAccount = $this->config->serviceAccountImpersonation;

        if ($serviceAccount === null) {
            return $federated;
        }

        /** @var non-empty-string $project the configuration checked the name */
        $project = explode('/', $serviceAccount)[1];
        /** @var non-empty-string $stsToken exchange() refuses an empty token */
        $stsToken = $federated->getToken();

        $client = ($this->iamClientFactory)($project, Credentials::fromAccessToken($stsToken, $project));

        return $client->serviceAccounts()->generateAccessToken($serviceAccount, $this->scopes);
    }

    /**
     * Exchange the current subject token at STS, as RFC 8693 describes.
     *
     * @throws StsException|SubjectTokenException
     */
    protected function exchange(): AccessTokenInterface
    {
        $subjectToken = $this->config->subjectTokenSupplier->subjectToken(
            new SupplierOptions($this->config->audience, $this->config->subjectTokenType),
        );

        try {
            $response = $this->httpClient->request('POST', $this->config->tokenUrl, [
                'form_params' => [
                    'grant_type' => self::GRANT_TYPE,
                    'subject_token' => $subjectToken,
                    'subject_token_type' => $this->config->subjectTokenType,
                    'requested_token_type' => self::REQUESTED_TOKEN_TYPE,
                    'audience' => $this->config->audience,
                ],
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new StsException("Could not reach STS at {$this->config->tokenUrl}: {$e->getMessage()}", previous: $e);
        }

        $status = $response->getStatusCode();

        try {
            $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $body = null;
        }

        if ($status < 200 || $status > 299) {
            $error = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : null;
            $description = is_array($body) && is_string($body['error_description'] ?? null) ? $body['error_description'] : null;

            $message = $error === null
                ? "STS refused the exchange with HTTP {$status}"
                : "STS refused the exchange: {$error}".($description === null ? '' : ": {$description}")." (HTTP {$status})";

            throw new StsException($message, $error, $description, $status);
        }

        $accessToken = is_array($body) ? ($body['access_token'] ?? null) : null;
        $expiresIn = is_array($body) ? ($body['expires_in'] ?? null) : null;

        // A token without an expiry would be reused until the process ends, so one is refused.
        if (!is_string($accessToken) || $accessToken === '' || !is_int($expiresIn) || $expiresIn <= 0) {
            throw new StsException('STS returned no access token or no expiry', statusCode: $status);
        }

        return new AccessToken([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
        ]);
    }

    /**
     * Whether a token is close enough to its expiry to be replaced.
     */
    protected function due(AccessTokenInterface $token): bool
    {
        $expires = $token->getExpires();

        return $expires === null || $expires - time() <= self::REFRESH_MARGIN;
    }
}
