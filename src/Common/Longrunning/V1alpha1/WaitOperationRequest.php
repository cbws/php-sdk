<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\WaitOperationRequest as WaitOperationRequestGrpc;
use Google\Protobuf\Duration;

class WaitOperationRequest
{
    protected WaitOperationRequestGrpc $object;

    public function __construct(?WaitOperationRequestGrpc $object = null)
    {
        $this->object = $object ?? new WaitOperationRequestGrpc();
    }

    public function withName(string $name): self
    {
        $this->object->setName($name);

        return $this;
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function withTimeout(int $timeoutMilliseconds): self
    {
        $seconds = (int) floor($timeoutMilliseconds / 1000);
        $nanos = ($timeoutMilliseconds % 1000) * 1000000;

        $duration = new Duration();
        $duration->setSeconds($seconds);
        $duration->setNanos($nanos);

        $this->object->setTimeout($duration);

        return $this;
    }

    public function toGrpc(): WaitOperationRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
        ];
    }
}
