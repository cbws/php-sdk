<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\GetImageRequest as GetImageRequestGrpc;

class GetImageRequest
{
    protected GetImageRequestGrpc $object;

    protected ?string $project = null;

    public function __construct(?GetImageRequestGrpc $object = null)
    {
        $this->object = $object ?? new GetImageRequestGrpc();
    }

    public function getProject(): ?string
    {
        return $this->project;
    }

    /**
     * Get an image of another project than the client's.
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

    public function toGrpc(): GetImageRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'project' => $this->getProject(),
            'name' => $this->getName(),
        ];
    }
}
