<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

/**
 * Serves an access token obtained elsewhere, such as one a pipeline reads from Vault.
 *
 * There is nothing to refresh it with, so once it expires every call fails until the caller supplies a new one. Its
 * expiry is not known here, so the token it serves has none.
 */
class StaticTokenSource implements TokenSourceContract
{
    /**
     * @param non-empty-string $accessToken
     */
    public function __construct(
        protected string $accessToken,
        protected ?string $project = null,
    ) {}

    public function token(): AccessTokenInterface
    {
        return new AccessToken([
            'access_token' => $this->accessToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * The project given with the token. A bare access token names none of its own.
     */
    public function getProject(): ?string
    {
        return $this->project;
    }
}
