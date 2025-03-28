<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use Cbws\Sdk\Auth\Oauth2\Cbws;
use DateTimeImmutable;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\Yaml\Yaml;

use const DIRECTORY_SEPARATOR;

class CLITokenSource implements TokenSourceContract
{
    protected string $filename;

    protected string $accessToken;

    protected int $expiresIn;

    protected ?DateTimeImmutable $expiry = null;

    protected ?string $refreshToken = null;

    protected string $tokenType;

    protected ?string $projectName = null;

    /**
     * @var string[]
     */
    protected array $scopes;

    protected Cbws $provider;

    /**
     * @param non-empty-string $filename
     * @param string[]         $scopes
     */
    public function __construct(string $filename, array $scopes = [])
    {
        $this->filename = $filename;
        $this->parseFile($filename);
        $this->scopes = $scopes;

        $this->provider = new Cbws();
    }

    /**
     * @param non-empty-string $filename
     */
    protected function parseFile(string $filename): void
    {
        $object = Yaml::parseFile($filename);
        assert(is_array($object));

        $token = $object['oauth_token'];
        assert(is_array($token));

        assert(is_string($token['accesstoken']));
        $this->accessToken = $token['accesstoken'];
        $this->refreshToken = $token['refreshtoken'] ?? null;

        if (isset($token['expiry'])) {
            $this->expiry = DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $token['expiry']) ?: null;
        }
        $this->expiresIn = $token['expiresin'] ?? null;
        $this->tokenType = $token['tokentype'] ?? 'bearer';
        $this->projectName = $object['project_name'] ?? null;
    }

    /**
     * @return array{project_name?: string, 'oauth_token': array{'accesstoken': string, 'expiresin': int, 'expiry': non-falsy-string|null, 'refreshtoken': string|null, 'tokentype': string}}
     */
    protected function toFile(): array
    {
        return array_filter([
            'project_name' => $this->projectName,
            'oauth_token' => [
                'accesstoken' => $this->accessToken,
                'expiresin' => $this->expiresIn,
                'expiry' => $this->expiry?->format(DATE_RFC3339_EXTENDED),
                'refreshtoken' => $this->refreshToken,
                'tokentype' => $this->tokenType,
            ],
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws IdentityProviderException
     */
    public function token(): AccessTokenInterface
    {
        $token = $this->getConfigToken();

        if (!$token->hasExpired()) {
            return $token;
        }

        $token = $this->provider->getAccessToken('refresh_token', [
            'client_id' => '612537ce-d5ba-475f-a77e-c1f1197ad3d5', // TODO
            'refresh_token' => $token->getRefreshToken(),
        ]);

        $this->setConfigToken($token);

        file_put_contents($this->filename, Yaml::dump($this->toFile()));

        return $token;
    }

    protected function getConfigToken(): AccessTokenInterface
    {
        return new AccessToken([
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires' => $this->expiry?->getTimestamp() ?? strtotime('2015-02-01'),
        ]);
    }

    protected function setConfigToken(AccessTokenInterface $token): void
    {
        $this->accessToken = $token->getToken();
        $this->refreshToken = $token->getRefreshToken();
        $this->expiry = $token->getExpires() ? (new DateTimeImmutable())->setTimestamp($token->getExpires()) : null;
    }

    /**
     * @return non-empty-string
     */
    public static function getFileLocation(): string
    {
        if (getenv('CBWS_CONFIG_FILE')) {
            return getenv('CBWS_CONFIG_FILE');
        }

        $home = $_SERVER['HOME'];
        assert(is_string($home));

        return $home.DIRECTORY_SEPARATOR.'.cbws.yaml';
    }

    public function getProject(): ?string
    {
        return $this->projectName;
    }
}
