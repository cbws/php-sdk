<?php

namespace Cbws\API\Client;

use Cbws\API\OAuth2\TokenSource;
use Grpc\CallCredentials;

class TokenSourceCallCredentials
{
    /**
     * @var TokenSource
     */
    public $tokenSource;

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

    public function credentialsPlugin()
    {
        return [
            'authorization' => ['Bearer ' . $this->getToken()],
        ];
    }

    public function getCallCredentials(): CallCredentials
    {
        return CallCredentials::createFromPlugin($this->getClosure());
    }
}
