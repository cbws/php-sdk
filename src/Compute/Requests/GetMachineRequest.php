<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\GetMachineRequest as GetMachineRequestGrpc;
use Cbws\Sdk\Common\ReadMaskTrait;

class GetMachineRequest
{
    use ReadMaskTrait;

    protected GetMachineRequestGrpc $object;

    public function __construct(?GetMachineRequestGrpc $object = null)
    {
        $this->object = $object ?? new GetMachineRequestGrpc();
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

    public function toGrpc(): GetMachineRequestGrpc
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
