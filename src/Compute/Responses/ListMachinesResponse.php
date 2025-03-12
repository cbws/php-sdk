<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as ListMachinesResponseGrpc;
use Cbws\Grpc\Compute\V1alpha1\Machine as MachineGrpc;
use Cbws\Sdk\Compute\Machine;

class ListMachinesResponse
{
    protected ListMachinesResponseGrpc $object;

    public function __construct(ListMachinesResponseGrpc $object)
    {
        $this->object = $object;
    }

    /**
     * @return Machine[]
     */
    public function getMachines(): array
    {
        $machines = [];

        /** @var MachineGrpc $machine */
        foreach ($this->object->getMachines() as $machine) {
            $machines[] = new Machine($machine);
        }

        return $machines;
    }

    public function getNextPageToken(): ?string
    {
        return $this->object->getNextPageToken() === '' ? null : $this->object->getNextPageToken();
    }

    public function __debugInfo()
    {
        return [
            'machines' => $this->getMachines(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
