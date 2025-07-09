<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\ReinstallMachineMetadata as ReinstallMachineMetadataGrpc;
use Cbws\Sdk\Common\Longrunning\MetadataInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<ReinstallMachineMetadataGrpc>
 */
class ReinstallMachineMetadata implements MetadataInterface
{
    protected ReinstallMachineMetadataGrpc $object;

    /**
     * @param ReinstallMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): ReinstallMachineMetadataGrpc
    {
        return $this->object;
    }
}
