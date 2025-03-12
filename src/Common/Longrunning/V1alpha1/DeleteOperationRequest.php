<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\DeleteOperationRequest as DeleteOperationRequestGrpc;

class DeleteOperationRequest
{
    protected DeleteOperationRequestGrpc $object;

    public function __construct(?DeleteOperationRequestGrpc $object = null)
    {
        $this->object = $object ?? new DeleteOperationRequestGrpc();
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

    public function toGrpc(): DeleteOperationRequestGrpc
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
