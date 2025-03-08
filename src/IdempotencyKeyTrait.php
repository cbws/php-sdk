<?php

namespace Cbws\Sdk;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

trait IdempotencyKeyTrait
{
    protected UuidInterface $idempotencyKey;

    public function getIdempotencyKey(): UuidInterface
    {
        return $this->idempotencyKey;
    }

    public function withIdempotencyKey(UuidInterface $idempotencyKey): self
    {
        $this->idempotencyKey = $idempotencyKey;

        return $this;
    }

    protected function generateIdempotencyKey(): UuidInterface
    {
        return Uuid::uuid4();
    }
}