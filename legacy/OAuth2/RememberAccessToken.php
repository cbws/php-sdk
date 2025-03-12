<?php

declare(strict_types=1);

namespace Cbws\API\OAuth2;

use League\OAuth2\Client\Token\AccessTokenInterface;

class RememberAccessToken implements TokenSource
{
    protected TokenSource $tokenSource;

    protected ?AccessTokenInterface $token = null;

    public function __construct(TokenSource $tokenSource)
    {
        $this->tokenSource = $tokenSource;
    }

    public function token(): AccessTokenInterface
    {
        if ($this->token && !$this->token->hasExpired()) {
            return $this->token;
        }

        return $this->token = $this->tokenSource->token();
    }
}
