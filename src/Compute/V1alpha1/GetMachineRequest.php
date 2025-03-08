<?php

namespace Cbws\Sdk\Compute\V1alpha1;

use Cbws\Sdk\IdempotencyKeyTrait;
use Cbws\Sdk\ReadMaskTrait;
use Google\Protobuf\FieldMask;

class GetMachineRequest
{
    use ReadMaskTrait;

    protected \Cbws\Grpc\Compute\V1alpha1\GetMachineRequest $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\GetMachineRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\GetMachineRequest ();
        }

        $this->object = $object;
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function withName(string $name = null): self
    {
        $this->object->setName($name);

        return $this;
    }

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\GetMachineRequest
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
        ];
    }

    public static function create(): self
    {
        return new self();
    }
}
