<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Models;

use Cbws\Grpc\Compute\V1alpha1\ImageFamily as ImageFamilyGrpc;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Compute\ImageFamilies;
use Cbws\Sdk\Compute\Requests\GetImageFromFamilyRequest;

/**
 * A named series of images, such as all the images of one operating system release. A machine created or reinstalled
 * from a family gets the family's current image for its machine type.
 */
class ImageFamily
{
    protected ImageFamilyGrpc $object;

    protected ?ImageFamilies $client = null;

    public function __construct(?ImageFamilyGrpc $object = null, ?ImageFamilies $client = null)
    {
        $this->object = $object ?? new ImageFamilyGrpc();
        $this->client = $client;
    }

    public function getID(): string
    {
        return explode('/', $this->getName())[3];
    }

    /**
     * The resource name of the family, such as `projects/my-project-123/families/ubuntu-2404-lts`.
     */
    public function getName(): string
    {
        return $this->object->getName();
    }

    /**
     * The project that holds the family, such as `projects/my-project-123`.
     */
    public function getParent(): string
    {
        return $this->object->getParent();
    }

    /**
     * The id of the project that holds the family, such as `my-project-123`.
     */
    public function getProject(): string
    {
        return explode('/', $this->getName())[1];
    }

    /**
     * Get the image a machine of the given type boots from this family now.
     *
     * @throws StatusException
     */
    public function getImage(string $machineType, GetImageFromFamilyRequest $request = new GetImageFromFamilyRequest()): Image
    {
        assert($this->client !== null);

        return $this->client->getImage($this->getID(), $machineType, $request->withProject($this->getProject()));
    }

    public function toGrpc(): ImageFamilyGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'parent' => $this->getParent(),
        ];
    }
}
