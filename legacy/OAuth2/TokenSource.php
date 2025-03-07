<?php

namespace Cbws\API\OAuth2;

use League\OAuth2\Client\Token\AccessTokenInterface;

interface TokenSource
{
    public function token(): \League\OAuth2\Client\Token\AccessTokenInterface;
}
