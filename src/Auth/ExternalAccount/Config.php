<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use GuzzleHttp\ClientInterface;
use JsonException;

/**
 * How a workload outside CBWS obtains its subject token and what it exchanges the token for. The JSON field names are
 * those of the Go SDK, which follow Google's external account configuration, so one file serves both.
 *
 * STS exchanges the subject token for a token that names the workload, and only IAM accepts that token. Where
 * $serviceAccountImpersonation names an account, IAM is asked for an ordinary access token for it in a second step.
 */
final class Config
{
    /**
     * The type a credential configuration file of this kind declares.
     */
    public const TYPE = 'external_account';

    public const DEFAULT_TOKEN_URL = 'https://sts.cbws.cloud/v1alpha1/token';

    /**
     * The subject token type of a JWT, such as a GitLab CI ID token or a projected Kubernetes service account token.
     */
    public const TOKEN_TYPE_JWT = 'urn:ietf:params:oauth:token-type:jwt';

    /**
     * The subject token type of an OpenID Connect ID token.
     */
    public const TOKEN_TYPE_ID_TOKEN = 'urn:ietf:params:oauth:token-type:id_token';

    private const AUDIENCE_PATTERN = '#^projects/([^/]+)/workloadIdentityPools/[^/]+/providers/[^/]+$#';

    private const SERVICE_ACCOUNT_PATTERN = '#^projects/([^/?\#]+)/serviceAccounts/[^/?\#]+$#';

    /**
     * @param string      $audience                    the provider that verifies the subject token, as
     *                                                 projects/{project}/workloadIdentityPools/{pool}/providers/{provider}
     * @param string|null $serviceAccountImpersonation the account whose token is returned, as
     *                                                 projects/{project}/serviceAccounts/{email}; null returns the STS
     *                                                 token itself, which only IAM accepts
     * @param string|null $iamEndpoint                 where IAM is called, null for the public endpoint
     *
     * @throws InvalidCredentialException
     */
    public function __construct(
        public readonly string $audience,
        public readonly string $subjectTokenType,
        public readonly SubjectTokenSupplier $subjectTokenSupplier,
        public readonly string $tokenUrl = self::DEFAULT_TOKEN_URL,
        public readonly ?string $serviceAccountImpersonation = null,
        public readonly ?string $iamEndpoint = null,
    ) {
        if (preg_match(self::AUDIENCE_PATTERN, $audience) !== 1) {
            throw new InvalidCredentialException(sprintf('audience %s is not a workload identity pool provider', json_encode($audience)));
        }

        if ($subjectTokenType !== self::TOKEN_TYPE_JWT && $subjectTokenType !== self::TOKEN_TYPE_ID_TOKEN) {
            throw new InvalidCredentialException(sprintf('subject_token_type %s is not supported, expected %s or %s', json_encode($subjectTokenType), self::TOKEN_TYPE_JWT, self::TOKEN_TYPE_ID_TOKEN));
        }

        $scheme = parse_url($tokenUrl, PHP_URL_SCHEME);
        $host = parse_url($tokenUrl, PHP_URL_HOST);

        if (($scheme !== 'https' && $scheme !== 'http') || !is_string($host) || $host === '') {
            throw new InvalidCredentialException(sprintf('token_url %s is not an HTTP URL', json_encode($tokenUrl)));
        }

        if ($serviceAccountImpersonation !== null && preg_match(self::SERVICE_ACCOUNT_PATTERN, $serviceAccountImpersonation) !== 1) {
            throw new InvalidCredentialException(sprintf('service_account_impersonation %s is not a service account name', json_encode($serviceAccountImpersonation)));
        }
    }

    /**
     * Parse a configuration file of TYPE.
     *
     * @param ClientInterface|null $httpClient the HTTP client of a url credential_source
     *
     * @throws InvalidCredentialException
     */
    public static function fromJson(string $json, ?ClientInterface $httpClient = null): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidCredentialException('Could not parse the external account configuration: '.$e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new InvalidCredentialException('The external account configuration is not an object');
        }

        $type = self::optionalString($data, 'type');

        if ($type !== null && $type !== self::TYPE) {
            throw new InvalidCredentialException(sprintf('Configuration has type %s, expected %s', json_encode($type), self::TYPE));
        }

        $impersonation = self::optionalString($data, 'service_account_impersonation');

        if ($impersonation === '') {
            $impersonation = null;
        }

        if (!array_key_exists('credential_source', $data) || !is_array($data['credential_source'])) {
            throw new InvalidCredentialException('The external account configuration has no credential_source');
        }

        return new self(
            self::optionalString($data, 'audience') ?? '',
            self::optionalString($data, 'subject_token_type') ?? '',
            self::supplier($data['credential_source'], $impersonation, $httpClient),
            self::optionalString($data, 'token_url') ?? self::DEFAULT_TOKEN_URL,
            $impersonation,
            self::optionalString($data, 'iam_endpoint'),
        );
    }

    /**
     * The project the credentials act in: the project of the impersonated service account where the configuration
     * names one, and otherwise the project of the workload identity pool.
     */
    public function getProject(): ?string
    {
        if ($this->serviceAccountImpersonation !== null &&
            preg_match(self::SERVICE_ACCOUNT_PATTERN, $this->serviceAccountImpersonation, $match) === 1 &&
            $match[1] !== '-') {
            return $match[1];
        }

        if (preg_match(self::AUDIENCE_PATTERN, $this->audience, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    /**
     * @param array<mixed> $source
     *
     * @throws InvalidCredentialException
     */
    private static function supplier(array $source, ?string $impersonation, ?ClientInterface $httpClient): SubjectTokenSupplier
    {
        $file = self::optionalString($source, 'file');
        $url = self::optionalString($source, 'url');
        $executable = $source['executable'] ?? null;

        if (count(array_filter([$file, $url, $executable], static fn (mixed $value): bool => $value !== null && $value !== '')) !== 1) {
            throw new InvalidCredentialException('credential_source must set exactly one of file, url and executable');
        }

        $format = Format::fromConfig($source['format'] ?? null);

        if ($file !== null && $file !== '') {
            return new FileSupplier($file, $format);
        }

        if ($url !== null && $url !== '') {
            $headers = $source['headers'] ?? [];

            if (!is_array($headers)) {
                throw new InvalidCredentialException('credential_source headers is not an object');
            }

            $strings = [];

            foreach ($headers as $name => $value) {
                if (!is_string($value)) {
                    throw new InvalidCredentialException('credential_source headers must all be strings');
                }

                $strings[(string) $name] = $value;
            }

            return new UrlSupplier($url, $strings, $format, $httpClient);
        }

        if (!is_array($executable)) {
            throw new InvalidCredentialException('credential_source executable is not an object');
        }

        $timeout = $executable['timeout_millis'] ?? ExecutableSupplier::DEFAULT_TIMEOUT_MILLIS;

        if (!is_int($timeout)) {
            throw new InvalidCredentialException('credential_source executable timeout_millis is not a number');
        }

        return new ExecutableSupplier(self::optionalString($executable, 'command') ?? '', $timeout, $format, $impersonation);
    }

    /**
     * @param array<mixed> $data
     *
     * @throws InvalidCredentialException
     */
    private static function optionalString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value !== null && !is_string($value)) {
            throw new InvalidCredentialException("{$key} is not a string");
        }

        return $value;
    }
}
