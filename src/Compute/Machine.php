<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\Machine as MachineGrpc;
use Cbws\Sdk\Compute\Enums\MachineState;

class Machine
{
    public const FIELD_HOSTNAME = 'hostname';

    protected MachineGrpc $object;

    /**
     * @var array<string>
     */
    protected array $updatedFields = [];

    public function __construct(?MachineGrpc $object = null)
    {
        $this->object = $object ?? new MachineGrpc();
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
