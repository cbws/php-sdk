<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\CreateMachineResponse as CreateMachineResponseGrpc;

class CreateMachineResponse
{
    protected CreateMachineResponseGrpc $object;

    public function __construct(CreateMachineResponseGrpc $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): CreateMachineResponseGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [];
    }
}
