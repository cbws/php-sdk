<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

use Google\Protobuf\FieldMask;

class UpdateInstanceRequest
{
    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\UpdateInstanceRequest
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\UpdateInstanceRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\VirtualMachines\Grpc\V1alpha1\UpdateInstanceRequest();
        }

        $this->object = $object;
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

    public function getFields(): ?array
    {
        if (is_null($this->object->getUpdateMask())) {
            return null;
        }

        return iterator_to_array($this->object->getUpdateMask()->getPaths());
    }

    public function withFields(string ...$fields): self
    {
        $fieldMask = new FieldMask();
        $fieldMask->setPaths($fields);

        $this->object->setUpdateMask($fieldMask);

        return $this;
    }

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\UpdateInstanceRequest
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'instance' => $this->getInstance(),
            'fields' => $this->getFields(),
        ];
    }

    public static function create()
    {
        return new self();
    }
}
