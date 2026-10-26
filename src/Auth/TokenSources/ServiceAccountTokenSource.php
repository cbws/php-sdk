<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use Cbws\Sdk\Auth\Oauth2\Cbws;
use Cbws\Sdk\Auth\Oauth2\JWTClientCredentials;
use Cbws\Sdk\Common\Exception\FileNotFoundException;
use Cbws\Sdk\Common\Exception\HomeNotDefinedException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Lcobucci\JWT\Signer\Key;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use stdClass;

use const DIRECTORY_SEPARATOR;

class ServiceAccountTokenSource implements TokenSourceContract
{
    /**
     * @var non-empty-string
     */
    protected string $projectName;

    /**
     * @var non-empty-string
     */
    protected string $privateKeyID;

    /**
     * @var non-empty-string
     */
    protected string $privateKey;

    /**
     * @var non-empty-string
     */
    protected string $clientEmail;

    /**
     * @var non-empty-string
     */
    protected string $clientID;

    /**
     * @var string[]
     */
    private array $scopes;

    protected AbstractProvider $provider;

    /**
     * @param string[] $scopes
     *
     * @throws FileNotFoundException
     * @throws InvalidCredentialException
     * @throws JsonException
     */
    public function __construct(
        ?string $filename = null,
        array $scopes = [],
    ) {
        if ($filename !== null) {
            $this->parseFile($filename);
        }

        $this->scopes = $scopes;

        $this->provider = new Cbws();
        $this->provider->getGrantFactory()->setGrant('jwt_client_credentials', new JWTClientCredentials());
    }

    /**
     * @throws InvalidCredentialException
     * @throws JsonException
     */
    protected function parseFile(string $filename): void
    {
        $json = file_get_contents($filename);

        if (!$json) {
            throw new FileNotFoundException("File not found: {$filename}");
        }

        $this->parse($json);
    }

    /**
     * @throws InvalidCredentialException
     * @throws JsonException
     */
    protected function parse(string $json): void
    {
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if (
            !$data instanceof stdClass ||
            !is_string($data->type) ||
            !is_string($data->client_id) ||
            !is_string($data->project_name) ||
            !is_string($data->private_key) ||
            !is_string($data->client_email) ||
            !is_string($data->private_key_id) ||
            strlen($data->client_id) === 0 ||
            strlen($data->project_name) === 0 ||
            strlen($data->private_key) === 0 ||
            strlen($data->client_email) === 0 ||
            strlen($data->private_key_id) === 0
        ) {
            throw new InvalidCredentialException('Invalid credentials data');
        }

        if ($data->type !== 'service_account') {
            throw new InvalidCredentialException("Unknown credential type {$data->type}");
        }

        // TODO
        $this->clientID = $data->client_id;
        $this->clientEmail = $data->client_email;
        $this->privateKey = $data->private_key;
        $this->privateKeyID = $data->private_key_id;
        $this->projectName = $data->project_name;
    }

    /**
     * @throws GuzzleException|IdentityProviderException
     */
    public function token(): AccessTokenInterface
    {
        return $this->provider->getAccessToken('jwt_client_credentials', [
            'client_assertion' => JWTClientCredentials::generateAssertion(
                $this->clientID,
                Key\InMemory::plainText($this->privateKey),
                $this->privateKeyID,
            ),
            'scope' => implode(',', $this->scopes),
        ]);
    }

    /**
     * @return non-empty-string
     *
     * @throws HomeNotDefinedException
     */
    public static function getFileLocation(): string
    {
        if (!isset($_SERVER['HOME']) && !is_string($_SERVER['HOME'])) {
            throw new HomeNotDefinedException();
        }
        /** @var string $home */
        $home = $_SERVER['HOME'];

        return $home.DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'cbws'.DIRECTORY_SEPARATOR.'cbws.json';
    }

    /**
     * @param string[] $scopes
     *
     * @throws FileNotFoundException
     * @throws InvalidCredentialException
     * @throws JsonException
     */
    public static function fromJson(string $json, array $scopes = []): self
    {
        $tokenSource = new self(null, $scopes);
        $tokenSource->parse($json);

        return $tokenSource;
    }

    public function getProject(): ?string
    {
        return $this->projectName;
    }
}
