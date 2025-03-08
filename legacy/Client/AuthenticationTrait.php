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
        // Either user hasn't provided default token source or we couldn't determine a default automatically
        if (is_null($this->tokenSource)) {
            throw new \Exception('No token source provided');
        }

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
