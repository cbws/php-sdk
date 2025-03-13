<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Compute\V1alpha1\CreateMachineMetadata as CreateMachineMetadataGrpc;
use Cbws\Grpc\Compute\V1alpha1\CreateMachineResponse as CreateMachineResponseGrpc;
use Cbws\Grpc\Compute\V1alpha1\StopMachineMetadata as StopMachineMetadataGrpc;
use Cbws\Grpc\Compute\V1alpha1\StopMachineResponse as StopMachineResponseGrpc;
use Cbws\Grpc\Longrunning\Operation as OperationGrpc;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Compute\Metadata\CreateMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StopMachineMetadata;
use Cbws\Sdk\Compute\Responses\CreateMachineResponse;
use Cbws\Sdk\Compute\Responses\StopMachineResponse;
use Exception;

/**
 * @template TMetadata of object
 * @template TResponse of object
 */
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
     *
     * @returns ?TMetadata
     */
    public function getMetadata(): mixed
    {
        $metadata = $this->object->getMetadata();

        if ($metadata === null) {
            return null;
        }

        switch ($metadata->getTypeUrl()) {
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineMetadata':
                assert($metadata->unpack() instanceof CreateMachineMetadataGrpc);

                return new CreateMachineMetadata($metadata->unpack());
            case 'type.googleapis.com/cbws.compute.v1alpha1.StopMachineMetadata':
                assert($metadata->unpack() instanceof StopMachineMetadataGrpc);

                return new StopMachineMetadata($metadata->unpack());
        }

        throw new Exception('Unknown metadata: '.$metadata->getTypeUrl());
    }

    public function getDone(): bool
    {
        return $this->object->getDone();
    }

    /**
     * @return ?TResponse
     *
     * @throws Exception
     */
    public function getResponse(): mixed
    {
        $response = $this->object->getResponse();

        if ($response === null) {
            return null;
        }

        switch ($response->getTypeUrl()) {
            case 'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineResponse':
                assert($response->unpack() instanceof CreateMachineResponseGrpc);

                return new CreateMachineResponse($response->unpack());
            case 'type.googleapis.com/cbws.compute.v1alpha1.StopMachineResponse':
                assert($response->unpack() instanceof StopMachineResponseGrpc);

                return new StopMachineResponse($response->unpack());
            case 'type.googleapis.com/google.protobuf.Empty':
                return null;
        }

        throw new Exception('Unknown response: '.$response->getTypeUrl());
    }

    public function getError(): ?StatusException
    {
        if ($this->object->getError() === null) {
            return null;
        }

        return StatusException::fromStatusMessage($this->object->getError());
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
