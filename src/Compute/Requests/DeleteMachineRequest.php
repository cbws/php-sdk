<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Sdk\Common\IdempotencyKeyTrait;

class DeleteMachineRequest
{
    use IdempotencyKeyTrait;

    protected \Cbws\Grpc\Compute\V1alpha1\DeleteMachineRequest $object;

    public function __construct(?\Cbws\Grpc\Compute\V1alpha1\DeleteMachineRequest $object = null)
    {
        if ($object === null) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\DeleteMachineRequest();
        }

        $this->idempotencyKey = $this->generateIdempotencyKey();
        $this->object = $object;
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

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\DeleteMachineRequest
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
