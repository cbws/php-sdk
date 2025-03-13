<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\CreateMachineRequest as CreateMachineRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;
use Cbws\Sdk\Compute\Machine;

class CreateMachineRequest
{
    use IdempotencyKeyTrait;

    protected CreateMachineRequestGrpc $object;

    public function __construct(?CreateMachineRequestGrpc $object = null)
    {
        $this->object = $object ?? new CreateMachineRequestGrpc();
        $this->idempotencyKey = $this->generateIdempotencyKey();
    }

    public function getParent(): ?string
    {
        return $this->object->getParent() === '' ? null : $this->object->getParent();
    }

    public function withParent(?string $parent = null): self
    {
        $this->object->setParent($parent ?? '');

        return $this;
    }

    public function withID(string $id): self
    {
        $this->object->setMachineId($id);

        return $this;
    }

    public function withMachine(Machine $machine): self
    {
        $this->object->setMachine($machine->toGrpc());

        return $this;
    }

    public function toGrpc(): CreateMachineRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'parent' => $this->getParent(),
            //            'pageSize' => $this->getPageSize(),
            //            'pageToken' => $this->getPageToken(),
            //            'fields' => $this->getFields(),
        ];
    }
}
