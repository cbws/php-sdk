<?php

namespace Cbws\API\OAuth2;

interface TokenSource
{
    public function token(): \League\OAuth2\Client\Token\AccessTokenInterface;
}
