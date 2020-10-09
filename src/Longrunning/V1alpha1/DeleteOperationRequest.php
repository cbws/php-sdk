<?php

namespace Cbws\API\Longrunning\V1alpha1;

class DeleteOperationRequest
{
    /**
     * @var \Google\LongRunning\DeleteOperationRequest
     */
    protected $object;

    public function __construct(\Google\LongRunning\DeleteOperationRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Google\LongRunning\DeleteOperationRequest();
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

    public function toGrpc(): \Google\LongRunning\DeleteOperationRequest
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
