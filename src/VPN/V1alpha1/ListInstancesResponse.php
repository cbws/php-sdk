<?php

namespace Cbws\API\VPN\V1alpha1;

class ListInstancesResponse
{
    /**
     * @var \Cbws\VPN\GRPC\V1alpha1\ListInstancesResponse
     */
    protected $object;

    public function __construct(\Cbws\VPN\GRPC\V1alpha1\ListInstancesResponse $object)
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

    public function getNextPageToken(): string
    {
        return $this->object->getNextPageToken();
    }

    public function __debugInfo()
    {
        return [
            'instances' => $this->getInstances(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
