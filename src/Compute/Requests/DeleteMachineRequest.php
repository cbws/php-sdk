<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\DeleteMachineRequest as DeleteMachineRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;

class DeleteMachineRequest
{
    use IdempotencyKeyTrait;

    protected DeleteMachineRequestGrpc $object;

    public function __construct(?DeleteMachineRequestGrpc $object = null)
    {
        $this->object = $object ?? new DeleteMachineRequestGrpc();
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

    public function toGrpc(): DeleteMachineRequestGrpc
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
