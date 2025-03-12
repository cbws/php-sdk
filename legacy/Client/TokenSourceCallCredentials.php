<?php

declare(strict_types=1);

namespace Cbws\API\Client;

use Cbws\API\OAuth2\TokenSource;
use Closure;
use Grpc\CallCredentials;

class TokenSourceCallCredentials
{
    public TokenSource $tokenSource;

    public function __construct(TokenSource $tokenSource)
    {
        $this->tokenSource = $tokenSource;
    }

    public function getToken(): string
    {
        return $this->tokenSource->token()->getToken();
    }

    public function getClosure(): callable
    {
        return [$this, 'credentialsPlugin'];
    }

    /**
     * @return array<string, string[]>
     */
    public function credentialsPlugin(): array
    {
        return [
            'authorization' => ['Bearer '.$this->getToken()],
        ];
    }

    public function getCallCredentials(): CallCredentials
    {
        return CallCredentials::createFromPlugin($this->getClosure());
    }
}
