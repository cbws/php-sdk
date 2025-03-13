<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Compute\V1alpha1\CreateMachineMetadata as CreateMachineMetadataGrpc;
use Cbws\Grpc\Compute\V1alpha1\CreateMachineResponse as CreateMachineResponseGrpc;
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

    /**
     * @throws Exception
     */
    public function getMetadata(): ?object
    {
        $metadata = $this->object->getMetadata();

        if ($metadata === null) {
            return null;
        }

        switch ($metadata->getTypeUrl()) {
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineMetadata':
                assert($metadata->unpack() instanceof CreateMachineMetadataGrpc);

                return new CreateMachineMetadata($metadata->unpack());
        }

        throw new Exception('Unknown metadata: '.$metadata->getTypeUrl());
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

    /**
     * @throws Exception
     */
    public function getResponse(): ?object
    {
        $response = $this->object->getResponse();

        if ($response === null) {
            return null;
        }

        switch ($response->getTypeUrl()) {
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineResponse':
                assert($response->unpack() instanceof CreateMachineResponseGrpc);

                return new CreateMachineResponse($response->unpack());
            case 'type.googleapis.com/google.protobuf.Empty':
                return null;
        }

        throw new Exception('Unknown response: '.$response->getTypeUrl());
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
