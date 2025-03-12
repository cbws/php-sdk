<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\StopMachineRequest as StopMachineRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;

class StopMachineRequest
{
    use IdempotencyKeyTrait;

    protected StopMachineRequestGrpc $object;

    public function __construct(?StopMachineRequestGrpc $object = null)
    {
        $this->idempotencyKey = $this->generateIdempotencyKey();
        $this->object = $object ?? new StopMachineRequestGrpc();
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

    public function toGrpc(): StopMachineRequestGrpc
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
