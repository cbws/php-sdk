<?php

namespace Cbws\Sdk\Compute\V1alpha1;

class CreateMachineResponse
{
    protected \Cbws\Grpc\Compute\V1alpha1\CreateMachineResponse $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\CreateMachineResponse $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\CreateMachineResponse
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
        ];
    }
}