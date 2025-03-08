<?php

namespace Cbws\Sdk\Longrunning\V1alpha1;

class CancelOperationRequest
{
    /**
     * @var \Cbws\Grpc\Longrunning\CancelOperationRequest
     */
    protected $object;

    public function __construct(\Cbws\Grpc\Longrunning\CancelOperationRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Longrunning\CancelOperationRequest();
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

    public function toGrpc(): \Cbws\Grpc\Longrunning\CancelOperationRequest
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
