<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\ResetMachineRequest as ResetMachineRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;

class ResetMachineRequest
{
    use IdempotencyKeyTrait;

    protected ResetMachineRequestGrpc $object;

    public function __construct(?ResetMachineRequestGrpc $object = null)
    {
        $this->idempotencyKey = $this->generateIdempotencyKey();
        $this->object = $object ?? new ResetMachineRequestGrpc();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function withName(?string $name = null): self
    {
        $this->object->setName($name);

        return $this;
    }

    public function toGrpc(): ResetMachineRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
