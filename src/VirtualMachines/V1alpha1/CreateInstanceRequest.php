<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

class CreateInstanceRequest
{
    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\CreateInstanceRequest
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\CreateInstanceRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\VirtualMachines\Grpc\V1alpha1\CreateInstanceRequest();
        }

        $this->object = $object;
    }

    public function getParent(): string
    {
        return $this->object->getParent();
    }

    public function withParent(string $parent): self
    {
        $this->object->setParent($parent);

        return $this;
    }

    public function getID(): string
    {
        return $this->object->getInstanceId();
    }

    public function withID(string $id): self
    {
        $this->object->setInstanceId($id);

        return $this;
    }

    public function getInstance(): Instance
    {
        return new Instance($this->object->getInstance());
    }

    public function withInstance(Instance $instance): self
    {
        $this->object->setInstance($instance->toGrpc());

        return $this;
    }

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\CreateInstanceRequest
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'parent' => $this->getParent(),
            'id' => $this->getID(),
            'instance' => $this->getInstance(),
        ];
    }

    public static function create()
    {
        return new self();
    }
}
