<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\CancelOperationRequest as CancelOperationRequestGrpc;

class CancelOperationRequest
{
    protected CancelOperationRequestGrpc $object;

    public function __construct(?CancelOperationRequestGrpc $object = null)
    {
        $this->object = $object ?? new CancelOperationRequestGrpc();
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

    public function toGrpc(): CancelOperationRequestGrpc
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
