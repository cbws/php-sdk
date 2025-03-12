<?php

declare(strict_types=1);

namespace Cbws\API\OAuth2\ServiceAccount;

use Cbws\API\OAuth2\Cloudbear\Cbws;
use Cbws\API\OAuth2\Cloudbear\JWTClientCredentials;
use Cbws\API\OAuth2\TokenSource as BaseTokenSource;
use Lcobucci\JWT\Signer\Key;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;

class TokenSource implements BaseTokenSource
{
    protected string $clientID;

    /**
     * @var non-empty-string
     */
    protected string $privateKey;

    protected string $privateKeyID;

    protected AbstractProvider $provider;

    /**
     * @var string[]
     */
    private array $scopes;

    /**
     * @param string[] $scopes
     */
    public function __construct(string $clientID, string $privateKey, string $privateKeyID, array $scopes)
    {
        assert($privateKey !== ''); // TODO

        $this->clientID = $clientID;
        $this->privateKey = $privateKey;
        $this->privateKeyID = $privateKeyID;
        $this->scopes = $scopes;

        $this->provider = new Cbws();
        $this->provider->getGrantFactory()->setGrant('jwt_client_credentials', new JWTClientCredentials());
    }

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
}
