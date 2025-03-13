<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use League\OAuth2\Client\Token\AccessTokenInterface;

class RememberAccessToken implements TokenSourceContract
{
    protected TokenSourceContract $tokenSource;

    protected ?AccessTokenInterface $token = null;

    public function __construct(TokenSourceContract $tokenSource)
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

    public function getProject(): ?string
    {
        return $this->tokenSource->getProject();
    }
}
