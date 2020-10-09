<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

use Google\Protobuf\FieldMask;

class GetInstanceRequest
{
    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\GetInstanceRequest
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\GetInstanceRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\VirtualMachines\Grpc\V1alpha1\GetInstanceRequest();
        }

        $this->object = $object;
    }

    public function withName(string $name = null): self
    {
        if (is_null($name)) {
            $name = '';
        }

        $this->object->setName($name);

        return $this;
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getFields(): ?array
    {
        if (is_null($this->object->getReadMask())) {
            return null;
        }

        return iterator_to_array($this->object->getReadMask()->getPaths());
    }

    public function withFields(string ...$fields): self
    {
        $fieldMask = new FieldMask();
        $fieldMask->setPaths($fields);

        $this->object->setReadMask($fieldMask);

        return $this;
    }

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\GetInstanceRequest
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'fields' => $this->getFields(),
        ];
    }

    public static function create()
    {
        return new self();
    }
}
