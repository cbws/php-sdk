<?php

declare(strict_types=1);

namespace Cbws\API\Client;

use Cbws\API\OAuth2\Credentials;
use Cbws\API\OAuth2\TokenSource;
use Exception;
use Grpc\CallCredentials;

trait AuthenticationTrait
{
    protected ?TokenSource $tokenSource = null;

    protected TokenSourceCallCredentials $tokenCallCredentials;

    public function withTokenSource(TokenSource $tokenSource): self
    {
        $this->tokenSource = $tokenSource;

        return $this;
    }

    protected function getCallCredentials(): CallCredentials
    {
        // Either user hasn't provided default token source or we couldn't determine a default automatically
        if ($this->tokenSource === null) {
            throw new Exception('No token source provided'); // TODO create exception class
        }

        $this->tokenCallCredentials = new TokenSourceCallCredentials($this->tokenSource);

        return $this->tokenCallCredentials->getCallCredentials();
    }

    protected function getDefaultTokenSource(): ?TokenSource
    {
        return Credentials::findDefault([])?->getTokenSource();
    }
}
