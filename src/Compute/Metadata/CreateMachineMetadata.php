<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Cbws\Grpc\Compute\V1alpha1\CreateMachineMetadata as CreateMachineMetadataGrpc;
use Cbws\Sdk\Common\Longrunning\MetadataInterface;
use Cbws\Sdk\Compute\Enums\MachineState;
use Cbws\Sdk\Compute\Models\Machine;
use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<CreateMachineMetadataGrpc>
 */
class CreateMachineMetadata implements MetadataInterface
{
    protected CreateMachineMetadataGrpc $object;

    /**
     * @param CreateMachineMetadataGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    /**
     * Current state of the machine during the create machine call.
     */
    public function getState(): MachineState
    {
        return MachineState::from($this->object->getState());
    }

    /**
     * Current machine resource during the machine call.
     */
    public function getMachine(): Machine
    {
        return new Machine($this->object->getMachine());
    }

    public function toGrpc(): CreateMachineMetadataGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            //            'state' => $this->getState(),
            //            'machine' => $this->getMachine(),
        ];
    }
}
