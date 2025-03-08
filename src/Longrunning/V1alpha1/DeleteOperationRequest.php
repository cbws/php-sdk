<?php

namespace Cbws\Sdk\Longrunning\V1alpha1;

class DeleteOperationRequest
{
    /**
     * @var \Cbws\Grpc\Longrunning\DeleteOperationRequest
     */
    protected $object;

    public function __construct(\Cbws\Grpc\Longrunning\DeleteOperationRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Longrunning\DeleteOperationRequest();
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

    public function toGrpc(): \Cbws\Grpc\Longrunning\DeleteOperationRequest
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
        ];
    }

    public static function create(): self
    {
        return new self();
    }
}
