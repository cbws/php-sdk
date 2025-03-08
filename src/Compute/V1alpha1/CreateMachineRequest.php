<?php

namespace Cbws\Sdk\Compute\V1alpha1;

use Cbws\Sdk\IdempotencyKeyTrait;
use Google\Protobuf\FieldMask;

class CreateMachineRequest
{
    use IdempotencyKeyTrait;

    protected \Cbws\Grpc\Compute\V1alpha1\CreateMachineRequest $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\CreateMachineRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\CreateMachineRequest ();
        }

        $this->idempotencyKey = $this->generateIdempotencyKey();
        $this->object = $object;
    }

    public function getParent(): ?string
    {
        return ($this->object->getParent()) ? $this->object->getParent() : null;
    }

    public function withParent(string $parent = null): self
    {
        if (is_null($parent)) {
            $this->object->setParent('');

            return $this;
        }

        $this->object->setParent($parent);

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


    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\CreateMachineRequest
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

    public static function create(): self
    {
        return new self();
    }
}
