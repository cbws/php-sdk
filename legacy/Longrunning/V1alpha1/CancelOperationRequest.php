<?php

namespace Cbws\API\Longrunning\V1alpha1;

class CancelOperationRequest
{
    /**
     * @var \Google\LongRunning\CancelOperationRequest
     */
    protected $object;

    public function __construct(\Google\LongRunning\CancelOperationRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Google\LongRunning\CancelOperationRequest();
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

    public function toGrpc(): \Google\LongRunning\CancelOperationRequest
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
