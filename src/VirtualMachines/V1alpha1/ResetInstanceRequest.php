<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

class ResetInstanceRequest
{
    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\ResetInstanceRequest
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\ResetInstanceRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\VirtualMachines\Grpc\V1alpha1\ResetInstanceRequest();
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

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\ResetInstanceRequest
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
        ];
    }

    public static function create()
    {
        return new self();
    }
}
