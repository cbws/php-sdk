<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

class ListInstancesResponse
{
    /**
     * @var \Cbws\VirtualMachines\GRPC\V1alpha1\ListInstancesResponse
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\GRPC\V1alpha1\ListInstancesResponse $object)
    {
        $this->object = $object;
    }

    /**
     * @return Instance[]
     */
    public function getInstances(): array
    {
        $instances = [];
        foreach ($this->object->getInstances() as $instance) {
            $instances[] = new Instance($instance);
        }

        return $instances;
    }

    public function getNextPageToken(): ?string
    {
        return ($this->object->getNextPageToken()) ? $this->object->getNextPageToken() : null;
    }

    public function __debugInfo()
    {
        return [
            'instances' => $this->getInstances(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
