<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\StopMachineMetadata as StopMachineMetadataGrpc;
use Cbws\Sdk\Common\Longrunning\MetadataInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<StopMachineMetadataGrpc>
 */
class StopMachineMetadata implements MetadataInterface
{
    protected StopMachineMetadataGrpc $object;

    /**
     * @param StopMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): StopMachineMetadataGrpc
    {
        return $this->object;
    }
}
