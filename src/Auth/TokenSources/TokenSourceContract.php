<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\TokenSources;

use League\OAuth2\Client\Token\AccessTokenInterface;

interface TokenSourceContract
{
    public function token(): AccessTokenInterface;

    public function getProject(): ?string;
}
