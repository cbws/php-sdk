<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\ListOperationsResponse as ListOperationsResponseGrpc;
use Cbws\Grpc\Longrunning\Operation as OperationGrpc;
use Cbws\Sdk\Common\Longrunning\MetadataInterface;
use Cbws\Sdk\Common\Longrunning\Model\Operation;
use Cbws\Sdk\Common\Longrunning\Operations;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Cbws\Sdk\Common\Longrunning\UnknownMetadata;
use Cbws\Sdk\Common\Longrunning\UnknownResponse;
use Cbws\Sdk\Compute\Metadata\CreateMachineMetadata;
use Cbws\Sdk\Compute\Metadata\DeleteMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ReinstallMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ResizeMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StartMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StopMachineMetadata;
use Cbws\Sdk\Compute\Responses\CreateMachineResponse;
use Cbws\Sdk\Compute\Responses\ReinstallMachineResponse;
use Cbws\Sdk\Compute\Responses\ResizeMachineResponse;
use Cbws\Sdk\Compute\Responses\StartMachineResponse;
use Cbws\Sdk\Compute\Responses\StopMachineResponse;
use Exception;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\RepeatedField;

class ListOperationsResponse
{
    public function __construct(protected ListOperationsResponseGrpc $object, protected Operations $client) {}

    /**
     * @return Operation<covariant MetadataInterface<Message>, covariant ResponseInterface<Message>>[]
     *
     * @throws Exception
     */
    public function getOperations(): array
    {
        $operations = [];

        /** @var RepeatedField<OperationGrpc> $grpcOperations */
        $grpcOperations = $this->object->getOperations();

        foreach ($grpcOperations as $grpcOperation) {
            $operations[] = self::operationFromGrpc($grpcOperation, $this->client);
        }

        return $operations;
    }

    public function getNextPageToken(): ?string
    {
        return $this->object->getNextPageToken() === '' ? null : $this->object->getNextPageToken();
    }

    /**
     * @throws Exception
     */
    public function __debugInfo()
    {
        return [
            'operations' => $this->getOperations(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }

    /**
     * @return Operation<covariant MetadataInterface<Message>, covariant ResponseInterface<Message>>
     *
     * @throws Exception
     */
    protected function operationFromGrpc(OperationGrpc $grpcOperation, Operations $client): Operation
    {
        /**
         * @var class-string<covariant MetadataInterface<Message>>|null $metadataClazz
         */
        $metadataClazz = null;

        if ($grpcOperation->getMetadata() !== null) {
            $metadataClazz = match ($grpcOperation->getMetadata()->getTypeUrl()) {
                'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineMetadata' => CreateMachineMetadata::class,
                'type.googleapis.com/cbws.compute.v1alpha1.DeleteMachineMetadata' => DeleteMachineMetadata::class,
                'type.googleapis.com/cbws.compute.v1alpha1.ReinstallMachineMetadata' => ReinstallMachineMetadata::class,
                'type.googleapis.com/cbws.compute.v1alpha1.ResizeMachineMetadata' => ResizeMachineMetadata::class,
                'type.googleapis.com/cbws.compute.v1alpha1.StartMachineMetadata' => StartMachineMetadata::class,
                'type.googleapis.com/cbws.compute.v1alpha1.StopMachineMetadata' => StopMachineMetadata::class,
                default => UnknownMetadata::class,
            };
        }

        if ($grpcOperation->getResponse() !== null) {
            $responseClazz = match ($grpcOperation->getResponse()->getTypeUrl()) {
                'type.googleapis.com/cbws.compute.v1alpha1.CreateMachineResponse' => CreateMachineResponse::class,
                'type.googleapis.com/cbws.compute.v1alpha1.ReinstallMachineResponse' => ReinstallMachineResponse::class,
                'type.googleapis.com/cbws.compute.v1alpha1.ResizeMachineResponse' => ResizeMachineResponse::class,
                'type.googleapis.com/cbws.compute.v1alpha1.StartMachineResponse' => StartMachineResponse::class,
                'type.googleapis.com/cbws.compute.v1alpha1.StopMachineResponse' => StopMachineResponse::class,
                default => UnknownResponse::class,
            };
        } else {
            // We need to specify something, response can be null when the operation failed, it then has an error set.
            $responseClazz = UnknownResponse::class;
        }

        if (!$metadataClazz) {
            throw new Exception('No metadata clazz');
        }

        /** @var class-string<MetadataInterface<Message>> $metadataClazz */
        /** @var class-string<ResponseInterface<Message>> $responseClazz */
        return new Operation(
            $grpcOperation,
            $metadataClazz,
            $responseClazz,
            $client,
        );
    }
}
