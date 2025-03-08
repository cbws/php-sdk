<?php

namespace Cbws\Sdk\Longrunning\V1alpha1;

use Google\Protobuf\Duration;

class WaitOperationRequest
{
    protected \Cbws\Grpc\Longrunning\WaitOperationRequest $object;

    public function __construct(\Cbws\Grpc\Longrunning\WaitOperationRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Longrunning\WaitOperationRequest();
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

    public function withTimeout(int $timeoutMilliseconds): self
    {
        $seconds = (int)floor($timeoutMilliseconds / 1000);
        $nanos = ($timeoutMilliseconds % 1000) * 1000000;

        $duration = new Duration();
        $duration->setSeconds($seconds);
        $duration->setNanos($nanos);

        $this->object->setTimeout($duration);

        return $this;
    }

    public function toGrpc(): \Cbws\Grpc\Longrunning\WaitOperationRequest
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
