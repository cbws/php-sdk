<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Sdk\Common\ReadMaskTrait;

class GetMachineRequest
{
    use ReadMaskTrait;

    protected \Cbws\Grpc\Compute\V1alpha1\GetMachineRequest $object;

    public function __construct(?\Cbws\Grpc\Compute\V1alpha1\GetMachineRequest $object = null)
    {
        if ($object === null) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\GetMachineRequest();
        }

        $this->object = $object;
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
}
