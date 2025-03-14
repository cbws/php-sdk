<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\Client;

use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Grpc\CallCredentials;

class TokenSourceCallCredentials
{
    public TokenSourceContract $tokenSource;

    public function __construct(TokenSourceContract $tokenSource)
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
        /** @phpstan-ignore argument.type */
        return CallCredentials::createFromPlugin($this->getClosure());
    }
}
