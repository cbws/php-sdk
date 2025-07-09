<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\RequestMachineConsoleRequest as RequestMachineConsoleRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;

class RequestMachineConsoleRequest
{
    use IdempotencyKeyTrait;

    protected RequestMachineConsoleRequestGrpc $object;

    public function __construct(?RequestMachineConsoleRequestGrpc $object = null)
    {
        $this->object = $object ?? new RequestMachineConsoleRequestGrpc();
        $this->idempotencyKey = $this->generateIdempotencyKey();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function withName(?string $name = null): self
    {
        $this->object->setName($name ?? '');

        return $this;
    }

    public function toGrpc(): RequestMachineConsoleRequestGrpc
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
