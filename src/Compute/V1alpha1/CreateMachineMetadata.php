<?php

namespace Cbws\Sdk\Compute\V1alpha1;

class CreateMachineMetadata
{
    protected \Cbws\Grpc\Compute\V1alpha1\CreateMachineMetadata $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\CreateMachineMetadata $object)
    {
        $this->object = $object;
    }

    /**
     * Current state of the machine during the create machine call
     *
     * @return MachineState
     */
    public function getState(): MachineState
    {
        return MachineState::from($this->object->getState());
    }

    /**
     * Current machine resource during the machine call
     *
     * @return Machine
     */
    public function getMachine(): Machine
    {
        return new Machine($this->object->getMachine());
    }

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\CreateMachineMetadata
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'state' => $this->getState(),
            'machine' => $this->getMachine(),
        ];
    }
}