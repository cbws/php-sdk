<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\Oauth2;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Cbws extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected string $accountsHostname = 'accounts.cbws.cloud';

    public function getAccountsHostname(): string
    {
        return $this->accountsHostname;
    }

    public function getBaseAuthorizationUrl(): string
    {
        return 'https://'.$this->getAccountsHostname().'/oauth2/authorize';
    }

    /**
     * @param array<string, string> $params
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        return 'https://'.$this->getAccountsHostname().'/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return 'https://'.$this->getAccountsHostname().'/userinfo';
    }

    /**
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * @param array{'error': string, 'error_description': string, 'status_code': int} $data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new IdentityProviderException($data['error'].': '.$data['error_description'], $data['status_code'], $response);
        }
    }

    /**
     * @param array{'sub': string, 'email': string} $response
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwnerInterface
    {
        return new Principal($response['sub'], $response['email']);
    }
}
