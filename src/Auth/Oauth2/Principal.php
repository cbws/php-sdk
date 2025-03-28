<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\Oauth2;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class Principal implements ResourceOwnerInterface
{
    public function __construct(protected string $id, protected string $email) {}

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
        ];
    }
}
