<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\StartMachineRequest as StartMachineRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;

class StartMachineRequest
{
    use IdempotencyKeyTrait;

    protected StartMachineRequestGrpc $object;

    public function __construct(?StartMachineRequestGrpc $object = null)
    {
        $this->object = $object ?? new StartMachineRequestGrpc();
        $this->idempotencyKey = $this->generateIdempotencyKey();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function withName(?string $name = null): self
    {
        $this->object->setName($name ?? '');

        return $this;
    }

    public function toGrpc(): StartMachineRequestGrpc
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
