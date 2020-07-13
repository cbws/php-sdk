<?php

namespace Cbws\API\OAuth2\ServiceAccount;

use Cbws\API\OAuth2\Cloudbear\Cloudbear;
use Cbws\API\OAuth2\Cloudbear\JWTClientCredentials;
use Cbws\API\OAuth2\TokenSource as BaseTokenSource;
use Lcobucci\JWT\Signer\Key;
use League\OAuth2\Client\Token\AccessTokenInterface;

class TokenSource implements BaseTokenSource
{
    /**
     * @var string
     */
    protected $clientID;
    /**
     * @var string
     */
    protected $privateKey;
    /**
     * @var string
     */
    protected $privateKeyID;

    /**
     * @var \League\OAuth2\Client\Provider\AbstractProvider
     */
    protected $provider;
    /**
     * @var array
     */
    private $scopes;

    public function __construct(string $clientID, string $privateKey, string $privateKeyID, array $scopes)
    {
        $this->clientID = $clientID;
        $this->privateKey = $privateKey;
        $this->privateKeyID = $privateKeyID;
        $this->scopes = $scopes;

        $this->provider = new Cloudbear();
        $this->provider->getGrantFactory()->setGrant('jwt_client_credentials', new JWTClientCredentials());
    }

    public function token(): AccessTokenInterface
    {
        return $this->provider->getAccessToken('jwt_client_credentials', [
            'client_assertion' => JWTClientCredentials::generateAssertion(
                $this->clientID, new Key($this->privateKey), $this->privateKeyID
            ),
            'scope' => implode(',', $this->scopes),
        ]);
    }
}
