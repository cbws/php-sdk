<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\ReinstallMachineRequest as ReinstallMachineRequestGrpc;
use Cbws\Sdk\Common\IdempotencyKeyTrait;

class ReinstallMachineRequest
{
    use IdempotencyKeyTrait;

    protected ReinstallMachineRequestGrpc $object;

    public function __construct(?ReinstallMachineRequestGrpc $object = null)
    {
        $this->object = $object ?? new ReinstallMachineRequestGrpc();
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

    public function getImage(): string
    {
        return $this->object->getImage();
    }

    public function withImage(?string $image = null): self
    {
        $this->object->setImage($image ?? '');

        return $this;
    }

    public function toGrpc(): ReinstallMachineRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'image' => $this->getImage(),
        ];
    }
}
