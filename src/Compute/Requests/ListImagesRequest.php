<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\ListImagesRequest as ListImagesRequestGrpc;

class ListImagesRequest
{
    protected ListImagesRequestGrpc $object;

    public function __construct(?ListImagesRequestGrpc $object = null)
    {
        $this->object = $object ?? new ListImagesRequestGrpc();
    }

    public function getParent(): ?string
    {
        return $this->object->getParent() === '' ? null : $this->object->getParent();
    }

    public function withParent(?string $parent = null): self
    {
        $this->object->setParent($parent ?? '');

        return $this;
    }

    /**
     * List the images of another project than the client's.
     */
    public function withProject(?string $project = null): self
    {
        return $this->withParent($project === null ? null : "projects/{$project}");
    }

    public function getPageSize(): ?int
    {
        return $this->object->getPageSize() === 0 ? null : $this->object->getPageSize();
    }

    /**
     * Limit the page size to the set amount of images. The service may return fewer.
     *
     * @return $this
     */
    public function withPageSize(?int $pageSize = null): self
    {
        $this->object->setPageSize($pageSize ?? 0);

        return $this;
    }

    public function getPageToken(): ?string
    {
        return $this->object->getPageToken() === '' ? null : $this->object->getPageToken();
    }

    public function withPageToken(?string $pageToken = null): self
    {
        $this->object->setPageToken($pageToken ?? '');

        return $this;
    }

    public function toGrpc(): ListImagesRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'parent' => $this->getParent(),
            'pageSize' => $this->getPageSize(),
            'pageToken' => $this->getPageToken(),
        ];
    }
}
