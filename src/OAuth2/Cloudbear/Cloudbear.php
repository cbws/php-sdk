<?php

namespace Cbws\API\OAuth2\Cloudbear;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Cloudbear extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $accountsHostname = 'accounts.cloudbear.nl';

    public function getAccountsHostname()
    {
        return $this->accountsHostname;
    }

    public function getBaseAuthorizationUrl()
    {
        return 'https://' . $this->getAccountsHostname() . '/oauth2/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://' . $this->getAccountsHostname() . '/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        // TODO: Implement getResourceOwnerDetailsUrl() method.
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() !== 200) {
            throw new IdentityProviderException($data['error'] . ': ' . $data['message'], $data['code'], $response);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        // TODO: Implement createResourceOwner() method.
    }
}
