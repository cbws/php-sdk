<?php

namespace Cbws\API\Longrunning\V1alpha1;

use Cbws\API\Exception\StatusException;
use Cbws\API\VirtualMachines\V1alpha1\CreateInstanceMetadata;
use Cbws\API\VirtualMachines\V1alpha1\DeleteInstanceMetadata;
use Cbws\API\VirtualMachines\V1alpha1\Instance;

class Operation
{
    /**
     * @var \Google\LongRunning\Operation
     */
    protected $object;

    public function __construct(\Google\LongRunning\Operation $object)
    {
        $this->object = $object;
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getMetadata()
    {
        if (is_null($this->object->getMetadata())) {
            return null;
        }

        switch ($this->object->getMetadata()->getTypeUrl()) {
            case 'type.googleapis.com/cbws.virtual_machines.v1alpha1.CreateInstanceMetadata':
                \Cbws\VirtualMachines\Grpc\V1alpha1\Metadata\Vm::initOnce();

                return new CreateInstanceMetadata($this->object->getMetadata()->unpack());
            case 'type.googleapis.com/cbws.virtual_machines.v1alpha1.DeleteInstanceMetadata':
                \Cbws\VirtualMachines\Grpc\V1alpha1\Metadata\Vm::initOnce();

                return new DeleteInstanceMetadata($this->object->getMetadata()->unpack());
        }

        throw new \Exception('Unknown metadata: ' . $this->object->getMetadata()->getTypeUrl());
    }

    public function getDone(): bool
    {
        return $this->object->getDone();
    }

    public function getError(): ?StatusException
    {
        if (is_null($this->object->getError())) {
            return null;
        }

        return StatusException::fromStatusMessage($this->object->getError());
    }

    public function getResponse()
    {
        if (is_null($this->object->getResponse())) {
            return null;
        }

        switch ($this->object->getResponse()->getTypeUrl()) {
            case 'type.googleapis.com/cbws.virtual_machines.v1alpha1.Instance':
                \Cbws\VirtualMachines\Grpc\V1alpha1\Metadata\Instance::initOnce();

                return new Instance($this->object->getResponse()->unpack());
            case 'type.googleapis.com/google.protobuf.Empty':
                return null;
        }

        throw new \Exception('Unknown response: ' . $this->object->getResponse()->getTypeUrl());
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'metadata' => $this->getMetadata(),
            'done' => $this->getDone(),
            'error' => $this->getError(),
            'response' => $this->getResponse(),
        ];
    }
}
