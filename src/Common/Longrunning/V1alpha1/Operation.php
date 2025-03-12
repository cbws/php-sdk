<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\Operation as OperationGrpc;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Compute\Metadata\CreateMachineMetadata;
use Cbws\Sdk\Compute\Responses\CreateMachineResponse;
use Exception;

class Operation
{
    protected OperationGrpc $object;

    public function __construct(OperationGrpc $object)
    {
        $this->object = $object;
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getMetadata(): ?object
    {
        if ($this->object->getMetadata() === null) {
            return null;
        }

        switch ($this->object->getMetadata()->getTypeUrl()) {
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineMetadata':
                return new CreateMachineMetadata($this->object->getMetadata()->unpack());
        }

        throw new Exception('Unknown metadata: '.$this->object->getMetadata()->getTypeUrl());
    }

    public function getDone(): bool
    {
        return $this->object->getDone();
    }

    public function getError(): ?StatusException
    {
        if ($this->object->getError() === null) {
            return null;
        }

        return StatusException::fromStatusMessage($this->object->getError());
    }

    public function getResponse(): ?object
    {
        if ($this->object->getResponse() === null) {
            return null;
        }

        switch ($this->object->getResponse()->getTypeUrl()) {
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineResponse':
                return new CreateMachineResponse($this->object->getResponse()->unpack());
            case 'type.googleapis.com/google.protobuf.Empty':
                return null;
        }

        throw new Exception('Unknown response: '.$this->object->getResponse()->getTypeUrl());
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
