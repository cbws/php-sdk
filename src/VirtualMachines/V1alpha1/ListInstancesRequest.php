<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

use Google\Protobuf\FieldMask;

class ListInstancesRequest
{
    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\ListInstancesRequest
     */
    protected $object;

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\ListInstancesRequest $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\VirtualMachines\Grpc\V1alpha1\ListInstancesRequest();
        }

        $this->object = $object;
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

    public function withPageToken(string $pageToken = null): self
    {
        if (is_null($pageToken)) {
            $this->object->setPageToken('');

            return $this;
        }

        $this->object->setPageToken($pageToken);

        return $this;
    }

    public function getFields(): ?array
    {
        if (is_null($this->object->getReadMask())) {
            return null;
        }

        return iterator_to_array($this->object->getReadMask()->getPaths());
    }

    public function withFields(string ...$fields): self
    {
        $fieldMask = new FieldMask();
        $fieldMask->setPaths($fields);

        $this->object->setReadMask($fieldMask);

        return $this;
    }

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\ListInstancesRequest
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

    public static function create()
    {
        return new self();
    }
}
