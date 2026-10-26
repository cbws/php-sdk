<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\ListOperationsRequest as ListOperationsRequestGrpc;

class ListOperationsRequest
{
    protected ListOperationsRequestGrpc $object;

    public function __construct(?ListOperationsRequestGrpc $object = null)
    {
        $this->object = $object ?? new ListOperationsRequestGrpc();
    }

    public function getParent(): ?string
    {
        return $this->object->getName() === '' ? null : $this->object->getName();
    }

    public function withParent(?string $parent = null): self
    {
        $this->object->setName($parent ?? '');

        return $this;
    }

    public function withProject(?string $project = null): self
    {
        return $this->withParent($project === null ? null : "projects/{$project}");
    }

    public function getPageSize(): ?int
    {
        return $this->object->getPageSize() === 0 ? null : $this->object->getPageSize();
    }

    /**
     * Limit the page size to the set amount of machines, can't be more than 25.
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

    public function toGrpc(): ListOperationsRequestGrpc
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
