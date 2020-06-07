<?php

namespace Cbws\API\OAuth2;

use League\OAuth2\Client\Token\AccessTokenInterface;

class RememberAccessToken implements TokenSource
{
    /**
     * @var TokenSource
     */
    protected $tokenSource;

    /**
     * @var AccessTokenInterface
     */
    protected $token;

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
