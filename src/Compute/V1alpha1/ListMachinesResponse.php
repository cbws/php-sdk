<?php

namespace Cbws\Sdk\Compute\V1alpha1;

class ListMachinesResponse
{
    /**
     * @var \Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse
     */
    protected $object;

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse $object)
    {
        $this->object = $object;
    }

    /**
     * @return Machine[]
     */
    public function getMachines(): array
    {
        $machines = [];
        foreach ($this->object->getMachines() as $machine) {
            $machines[] = new Machine($machine);
        }

        return $machines;
    }

    public function getNextPageToken(): ?string
    {
        return ($this->object->getNextPageToken()) ? $this->object->getNextPageToken() : null;
    }

    public function __debugInfo()
    {
        return [
            'machines' => $this->getMachines(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
