<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Models;

use Cbws\Grpc\Compute\V1alpha1\Machine as MachineGrpc;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\Operation;
use Cbws\Sdk\Compute\Enums\MachineState;
use Cbws\Sdk\Compute\Machines;
use Cbws\Sdk\Compute\Metadata\DeleteMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ResetMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StartMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StopMachineMetadata;
use Cbws\Sdk\Compute\Requests\DeleteMachineRequest;
use Cbws\Sdk\Compute\Requests\ResetMachineRequest;
use Cbws\Sdk\Compute\Requests\StartMachineRequest;
use Cbws\Sdk\Compute\Requests\StopMachineRequest;
use Cbws\Sdk\Compute\Responses\ResetMachineResponse;
use Cbws\Sdk\Compute\Responses\StartMachineResponse;
use Cbws\Sdk\Compute\Responses\StopMachineResponse;

class Machine
{
    public const FIELD_HOSTNAME = 'hostname';

    protected MachineGrpc $object;

    protected ?Machines $client = null;

    /**
     * @var array<string>
     */
    protected array $updatedFields = [];

    public function __construct(?MachineGrpc $object = null, ?Machines $client = null)
    {
        $this->object = $object ?? new MachineGrpc();
        $this->client = $client;
    }

    public function getID(): string
    {
        return explode('/', $this->getName())[3];
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

    public function getParent(): string
    {
        return $this->object->getParent();
    }

    public function getHostname(): string
    {
        return $this->object->getHostname();
    }

    public function withHostname(string $hostname): self
    {
        if ($hostname === $this->getHostname()) {
            return $this;
        }

        $this->object->setHostname($hostname);
        $this->updatedFields[] = self::FIELD_HOSTNAME;

        return $this;
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function withType(?string $type = null): self
    {
        $this->object->setType($type ?? '');

        return $this;
    }

    public function getImage(): string
    {
        return $this->object->getImage();
    }

    public function withImage(?string $image = null): self
    {
        $this->object->setImage($image ?? '');

        return $this;
    }

    public function getUserdata(): ?string
    {
        return ($this->object->getUserdata()) ?: null;
    }

    public function withUserdata(?string $userdata = null): self
    {
        $this->object->setUserdata($userdata ?? '');

        return $this;
    }

    public function getState(): MachineState
    {
        return MachineState::from($this->object->getState());
    }

    public function toGrpc(): MachineGrpc
    {
        return $this->object;
    }

    /**
     * @return string[]
     */
    public function updatedFields(): array
    {
        return $this->updatedFields;
    }

    /**
     * @return Operation<StopMachineMetadata, StopMachineResponse>
     *
     * @throws StatusException
     */
    public function stop(StopMachineRequest $request = new StopMachineRequest()): Operation
    {
        return $this->client->stop($this->getID(), $request);
    }

    /**
     * @return Operation<StartMachineMetadata, StartMachineResponse>
     *
     * @throws StatusException
     */
    public function start(StartMachineRequest $request = new StartMachineRequest()): Operation
    {
        return $this->client->start($this->getID(), $request);
    }

    /**
     * @return Operation<ResetMachineMetadata, ResetMachineResponse>
     *
     * @throws StatusException
     */
    public function reset(ResetMachineRequest $request = new ResetMachineRequest()): Operation
    {
        return $this->client->reset($this->getID(), $request);
    }

    /**
     * @return Operation<DeleteMachineMetadata, null>
     *
     * @throws StatusException
     */
    public function delete(DeleteMachineRequest $request = new DeleteMachineRequest()): Operation
    {
        return $this->client->delete($this->getID(), $request);
    }

    public function __debugInfo()
    {
        $info = [
            'name' => $this->getName(),
            'parent' => $this->getParent(),
            'hostname' => $this->getHostname().$this->isUpdated('hostname'),
            'type' => $this->getType(),
            'image' => $this->getImage(),
            'userdata' => $this->getUserdata(),
            'state' => $this->getState(),
        ];

        if (!empty($this->updatedFields())) {
            $info['updatedFields'] = $this->updatedFields();
        }

        return $info;
    }

    protected function isUpdated(string $field): string
    {
        return in_array($field, $this->updatedFields, true) ? ' (to update)' : '';
    }
}
