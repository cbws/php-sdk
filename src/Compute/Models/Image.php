<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Models;

use Cbws\Grpc\Compute\V1alpha1\Image as ImageGrpc;

/**
 * An operating system image that machines are installed from.
 */
class Image
{
    protected ImageGrpc $object;

    public function __construct(?ImageGrpc $object = null)
    {
        $this->object = $object ?? new ImageGrpc();
    }

    public function getID(): string
    {
        return explode('/', $this->getName())[3];
    }

    /**
     * The resource name of the image, such as `projects/my-project-123/images/ubuntu-2404-lts-20260901`.
     */
    public function getName(): string
    {
        return $this->object->getName();
    }

    /**
     * The project that holds the image, such as `projects/my-project-123`.
     */
    public function getParent(): string
    {
        return $this->object->getParent();
    }

    /**
     * The resource name of the family the image belongs to, such as `projects/my-project-123/families/ubuntu-2404-lts`,
     * or null for an image in no family.
     */
    public function getFamily(): ?string
    {
        return $this->object->getFamily() === '' ? null : $this->object->getFamily();
    }

    public function toGrpc(): ImageGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'parent' => $this->getParent(),
            'family' => $this->getFamily(),
        ];
    }
}
