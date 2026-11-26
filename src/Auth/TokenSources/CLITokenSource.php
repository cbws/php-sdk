<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use Cbws\Sdk\Auth\Oauth2\Cbws;
use Cbws\Sdk\Common\Exception\HomeNotDefinedException;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use const DIRECTORY_SEPARATOR;

class CLITokenSource implements TokenSourceContract
{
    protected string $filename;

    protected string $accessToken;

    protected int $expiresIn;

    protected ?DateTimeImmutable $expiry = null;

    /**
     * The CLI writes Go's zero time for a token without an expiry, which Go's oauth2 treats as never
     * expiring. A missing expiry is something else, an unknown one, and is refreshed straight away.
     */
    protected bool $neverExpires = false;

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
     *
     * @throws ParseException
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
     *
     * @throws ParseException
     */
    protected function parseFile(string $filename): void
    {
        // The CLI writes the expiry as an unquoted timestamp, which YAML would otherwise turn into an int
        /** @var array{project_name?: string, 'oauth_token': array{'accesstoken': string, 'expiresin': int, 'expiry': DateTimeInterface|int|string|null, 'refreshtoken': string|null, 'tokentype': string}} $object */
        $object = Yaml::parseFile($filename, Yaml::PARSE_DATETIME);
        assert(is_array($object));

        $token = $object['oauth_token'];
        assert(is_array($token));

        assert(is_string($token['accesstoken']));
        $this->accessToken = $token['accesstoken'];
        $this->refreshToken = $token['refreshtoken'] ?? null;

        $expiry = self::parseExpiry($token['expiry'] ?? null);

        if ($expiry !== null && (int) $expiry->format('Y') <= 1) {
            $this->neverExpires = true;
        } else {
            $this->expiry = $expiry;
        }
        $this->expiresIn = $token['expiresin'] ?? null;
        $this->tokenType = $token['tokentype'] ?? 'bearer';
        $this->projectName = $object['project_name'] ?? null;
    }

    /**
     * An expiry in any form the CLI config has held it: a timestamp YAML parsed, or a string or Unix
     * time from a config written by something else.
     */
    protected static function parseExpiry(mixed $expiry): ?DateTimeImmutable
    {
        if ($expiry instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($expiry);
        }

        if (is_int($expiry)) {
            return (new DateTimeImmutable())->setTimestamp($expiry);
        }

        if (is_string($expiry) && $expiry !== '') {
            try {
                return new DateTimeImmutable($expiry);
            } catch (Exception) {
                return null;
            }
        }

        return null;
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
                'expiry' => $this->neverExpires ? '0001-01-01T00:00:00Z' : $this->expiry?->format(DATE_RFC3339_EXTENDED),
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

        if ($token->getExpires() === null || !$token->hasExpired()) {
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
        if ($this->neverExpires) {
            return new AccessToken([
                'access_token' => $this->accessToken,
                'refresh_token' => $this->refreshToken,
            ]);
        }

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
        $this->neverExpires = $this->expiry === null;
    }

    /**
     * @return non-empty-string
     *
     * @throws HomeNotDefinedException
     */
    public static function getFileLocation(): string
    {
        $configFile = getenv('CBWS_CONFIG_FILE');

        if (!empty($configFile)) {
            return $configFile;
        }

        if (!isset($_SERVER['HOME']) && !is_string($_SERVER['HOME'])) {
            throw new HomeNotDefinedException();
        }

        /** @var string $home */
        $home = $_SERVER['HOME'];

        return $home.DIRECTORY_SEPARATOR.'.cbws.yaml';
    }

    public function getProject(): ?string
    {
        return $this->projectName;
    }
}
