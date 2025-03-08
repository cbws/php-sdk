<?php

namespace Cbws\Sdk\Longrunning\V1alpha1;

use Cbws\API\VirtualMachines\V1alpha1\CreateInstanceMetadata;
use Cbws\API\VirtualMachines\V1alpha1\DeleteInstanceMetadata;
use Cbws\API\VirtualMachines\V1alpha1\Instance;
use Cbws\Sdk\Compute\V1alpha1\CreateMachineMetadata;
use Cbws\Sdk\Compute\V1alpha1\CreateMachineResponse;
use Cbws\Sdk\Exception\StatusException;

class Operation
{
    /**
     * @var \Cbws\Grpc\Longrunning\Operation
     */
    protected $object;

    public function __construct(\Cbws\Grpc\Longrunning\Operation $object)
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
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineMetadata':
                return new CreateMachineMetadata($this->object->getMetadata()->unpack());
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
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineResponse':
                return new CreateMachineResponse($this->object->getResponse()->unpack());
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
