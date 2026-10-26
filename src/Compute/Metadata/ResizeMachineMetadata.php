<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\ResizeMachineMetadata as ResizeMachineMetadataGrpc;
use Cbws\Sdk\Common\Longrunning\MetadataInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<ResizeMachineMetadataGrpc>
 */
class ResizeMachineMetadata implements MetadataInterface
{
    protected ResizeMachineMetadataGrpc $object;

    /**
     * @param ResizeMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): ResizeMachineMetadataGrpc
    {
        return $this->object;
    }
}
