<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\ResetMachineMetadata as ResetMachineMetadataGrpc;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<ResetMachineMetadataGrpc>
 */
class ResetMachineMetadata implements MetadataInterface
{
    protected ResetMachineMetadataGrpc $object;

    /**
     * @param ResetMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): ResetMachineMetadataGrpc
    {
        return $this->object;
    }
}
