<?php

namespace Cbws\Sdk\Compute\V1alpha1;

use Cbws\Sdk\ReadMaskTrait;

class ListMachinesRequest
{
    use ReadMaskTrait;

    /**
     * @var \Cbws\Grpc\Compute\V1alpha1\ListMachinesRequest
     */
    protected $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\ListMachinesRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\ListMachinesRequest ();
        }

        $this->object = $object;

        $this->withFields('machines.status', 'machines.type', 'machines.image');
    }

    public function getParent(): ?string
    {
        return ($this->object->getParent()) ? $this->object->getParent() : null;
    }

    public function withParent(string $parent = null): self
    {
        if (is_null($parent)) {
            $this->object->setParent('');

            return $this;
        }

        $this->object->setParent($parent);

        return $this;
    }

    public function withProject(string $project = null): self
    {
        if (is_null($project)) {
            return $this->withParent(null);
        }

        return $this->withParent('projects/' . $project);
    }

    public function getPageSize(): ?int
    {
        return ($this->object->getPageSize()) ? $this->object->getPageSize() : null;
    }

    /**
     * Limit the page size to the set amount of machines, can't be more than 25.
     *
     * @param int|null $pageSize
     * @return $this
     */
    public function withPageSize(int $pageSize = null): self
    {
        if (is_null($pageSize)) {
            $this->object->setPageSize(0);

            return $this;
        }

        $this->object->setPageSize($pageSize);

        return $this;
    }

    public function getPageToken(): ?string
    {
        return ($this->object->getPageToken()) ? $this->object->getPageToken() : null;
    }

    /**
     * To get the next page include the nextPageToken from the previous response.
     *
     * @param string|null $pageToken
     * @return $this
     */
    public function withPageToken(string $pageToken = null): self
    {
        if (is_null($pageToken)) {
            $this->object->setPageToken('');

            return $this;
        }

        $this->object->setPageToken($pageToken);

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

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\ListMachinesRequest
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

    public static function create(): self
    {
        return new self();
    }
}
