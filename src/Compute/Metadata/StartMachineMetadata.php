<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\StartMachineMetadata as StartMachineMetadataGrpc;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<StartMachineMetadataGrpc>
 */
class StartMachineMetadata implements MetadataInterface
{
    protected StartMachineMetadataGrpc $object;

    /**
     * @param StartMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): StartMachineMetadataGrpc
    {
        return $this->object;
    }
}
