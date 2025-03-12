<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Requests;

use Cbws\Grpc\Compute\V1alpha1\ListMachinesRequest as ListMachinesRequestGrpc;
use Cbws\Sdk\Common\ReadMaskTrait;

class ListMachinesRequest
{
    use ReadMaskTrait;

    protected ListMachinesRequestGrpc $object;

    public function __construct(?ListMachinesRequestGrpc $object = null)
    {
        if ($object === null) {
            $object = new ListMachinesRequestGrpc();
        }

        $this->object = $object;

        $this->withFields('machines.status', 'machines.type', 'machines.image');
    }

    public function getParent(): ?string
    {
        return $this->object->getParent() === '' ? null : $this->object->getParent();
    }

    public function withParent(?string $parent = null): self
    {
        if ($parent === null) {
            $this->object->setParent('');

            return $this;
        }

        $this->object->setParent($parent);

        return $this;
    }

    public function withProject(?string $project = null): self
    {
        return $this->withParent($project === null ? null : 'projects/'.$project);
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

    /**
     * Don't request the machine state for every machine, this will make listing machines a lot quicker, especially
     * with many machines.
     *
     * @return $this
     */
    public function withoutState(): self
    {
        $values = $this->getFields();

        if (($key = array_search('machines.status', $values)) !== false) {
            unset($values[$key]);
        }

        $this->withFields(...$values);

        return $this;
    }

    public function toGrpc(): ListMachinesRequestGrpc
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'parent' => $this->getParent(),
            'pageSize' => $this->getPageSize(),
            'pageToken' => $this->getPageToken(),
            'fields' => $this->getFields(),
        ];
    }
}
