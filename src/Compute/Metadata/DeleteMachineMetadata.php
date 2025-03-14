<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\DeleteMachineMetadata as DeleteMachineMetadataGrpc;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<DeleteMachineMetadataGrpc>
 */
class DeleteMachineMetadata implements MetadataInterface
{
    protected DeleteMachineMetadataGrpc $object;

    /**
     * @param DeleteMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): DeleteMachineMetadataGrpc
    {
        return $this->object;
    }
}
