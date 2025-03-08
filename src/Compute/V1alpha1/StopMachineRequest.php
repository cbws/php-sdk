<?php

namespace Cbws\Sdk\Compute\V1alpha1;

use Cbws\Sdk\IdempotencyKeyTrait;

class StopMachineRequest
{
    use IdempotencyKeyTrait;

    protected \Cbws\Grpc\Compute\V1alpha1\StopMachineRequest $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\StopMachineRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\StopMachineRequest ();
        }

        $this->idempotencyKey = $this->generateIdempotencyKey();
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

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\StopMachineRequest
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
