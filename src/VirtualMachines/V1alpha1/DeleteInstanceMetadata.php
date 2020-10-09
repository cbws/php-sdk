<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

use DateTimeInterface;

class DeleteInstanceMetadata
{
    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\DeleteInstanceMetadata
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\DeleteInstanceMetadata $object)
    {
        $this->object = $object;
    }

    public function getStartTime(): ?DateTimeInterface
    {
        if (is_null($this->object->getStartTime())) {
            return null;
        }

        return $this->object->getStartTime()->toDateTime();
    }

    public function getCancelTime(): ?DateTimeInterface
    {
        if (is_null($this->object->getCancelTime())) {
            return null;
        }

        return $this->object->getCancelTime()->toDateTime();
    }

    public function getEndTime(): ?DateTimeInterface
    {
        if (is_null($this->object->getEndTime())) {
            return null;
        }

        return $this->object->getEndTime()->toDateTime();
    }

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\DeleteInstanceMetadata
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'startTime' => $this->getStartTime(),
            'cancelTime' => $this->getCancelTime(),
            'endTime' => $this->getEndTime(),
        ];
    }
}
