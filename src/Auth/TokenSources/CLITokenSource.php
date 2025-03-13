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
     * @param string[] $scopes
     */
    public function __construct(string $filename, array $scopes = [])
    {
        $this->filename = $filename;
        $this->parseFile($filename);
        $this->scopes = $scopes;

        $this->provider = new Cbws();
    }

    protected function parseFile(string $filename): void
    {
        $object = Yaml::parseFile($filename);

        $this->accessToken = $object['access_token'];
        $this->refreshToken = $object['refresh_token'] ?? null;
        $this->expiry = DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $object['expires_in']) ?: null;
        $this->expiresIn = $object['expires_in'] ?? null;
        $this->tokenType = $object['token_type'] ?? 'bearer';
        $this->projectName = $object['project'] ?? null;
    }

    protected function toFile(): array
    {
        return array_filter([
            'project_name' => $this->projectName,
            'oauth_token' => [
                'accesstoken' => $this->accessToken,
                'expiresin' => $this->expiresIn,
                'expiry' => $this->expiresIn,
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

    public static function getFileLocation(): string
    {
        if (getenv('CBWS_CONFIG_FILE')) {
            return getenv('CBWS_CONFIG_FILE');
        }

        return $_SERVER['HOME'].DIRECTORY_SEPARATOR.'.cbws.yaml';
    }

    public function getProject(): ?string
    {
        return $this->projectName;
    }
}
