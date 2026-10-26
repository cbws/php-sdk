<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\GetImageFromFamilyRequest as GetImageFromFamilyRequestGrpc;

class GetImageFromFamilyRequest
{
    protected GetImageFromFamilyRequestGrpc $object;

    protected ?string $project = null;

    public function __construct(?GetImageFromFamilyRequestGrpc $object = null)
    {
        $this->object = $object ?? new GetImageFromFamilyRequestGrpc();
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    /**
     * Use an image family of another project than the client's.
     *
     * @return $this
     */
    public function withProject(?string $project = null): self
    {
        $this->project = $project;

        return $this;
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

    public function getMachineType(): string
    {
        return $this->object->getMachineType();
    }

    /**
     * The machine type the image is for, such as `zones/nl-ein-1/types/g4.2` or `machineTypes/g4.2`. A family can hold
     * different images for different machine types.
     *
     * @return $this
     */
    public function withMachineType(?string $machineType = null): self
    {
        $this->object->setMachineType($machineType ?? '');

        return $this;
    }

    public function toGrpc(): GetImageFromFamilyRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'project' => $this->getProject(),
            'name' => $this->getName(),
            'machineType' => $this->getMachineType(),
        ];
    }
}
