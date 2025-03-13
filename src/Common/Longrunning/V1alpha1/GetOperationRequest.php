<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\GetOperationRequest as GetOperationRequestGrpc;

class GetOperationRequest
{
    protected GetOperationRequestGrpc $object;

    public function __construct(?GetOperationRequestGrpc $object = null)
    {
        $this->object = $object ?? new GetOperationRequestGrpc();
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

    public function toGrpc(): GetOperationRequestGrpc
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
