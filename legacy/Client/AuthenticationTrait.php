<?php

namespace Cbws\API\Client;

use Cbws\API\OAuth2\Credentials;
use Cbws\API\OAuth2\TokenSource;
use Grpc\CallCredentials;

trait AuthenticationTrait
{
    /**
     * @var TokenSource
     */
    protected $tokenSource;

    /**
     * @var TokenSourceCallCredentials
     */
    protected $tokenCallCredentials;

    public function withTokenSource(TokenSource $tokenSource): self
    {
        $this->tokenSource = $tokenSource;

        return $this;
    }

    protected function getCallCredentials(): CallCredentials
    {
        $this->tokenCallCredentials = new TokenSourceCallCredentials($this->tokenSource);

        return $this->tokenCallCredentials->getCallCredentials();
    }

    protected function getDefaultTokenSource(): ?TokenSource
    {
        $credentials = Credentials::FindDefault([]);
        if (is_null($credentials)) {
            return null;
        }

        return $credentials->getTokenSource();
    }
}
