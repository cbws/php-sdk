<?php

namespace Cbws\Sdk\Compute\V1alpha1;

class Machine
{
    const FIELD_HOSTNAME = 'hostname';

    protected \Cbws\Grpc\Compute\V1alpha1\Machine $object;

    /**
     * @var array<string>
     */
    protected array $updatedFields = [];

    public function __construct(\Cbws\Grpc\Compute\V1alpha1\Machine $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\Grpc\Compute\V1alpha1\Machine();
        }

        $this->object = $object;
    }

    public function getID(): string
    {
        return explode('/', $this->getName())[3];
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function withName(string $name = null): self
    {
        if (is_null($name)) {
            $name = '';
        }

        $this->object->setName($name);

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

    public function withHostname(string $hostname = null): self
    {
        if ($hostname === $this->getHostname()) {
            return $this;
        }

        if (is_null($hostname)) {
            $hostname = '';
        }

        $this->object->setHostname($hostname);
        $this->updatedFields[] = self::FIELD_HOSTNAME;

        return $this;
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function withType(string $type = null): self
    {
        $this->object->setType($type);

        return $this;
    }

    public function getImage(): string
    {
        return $this->object->getImage();
    }

    public function withImage(string $image = null): self
    {
        $this->object->setImage($image);

        return $this;
    }

    public function getUserdata(): ?string
    {
        return ($this->object->getUserdata()) ?: null;
    }

    public function withUserdata(string $userdata = null): self
    {
        $this->object->setUserdata($userdata);

        return $this;
    }

    public function getState(): MachineState
    {
        return MachineState::from($this->object->getState());
    }

    public function toGrpc(): \Cbws\Grpc\Compute\V1alpha1\Machine
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
            'hostname' => $this->getHostname() . $this->isUpdated('hostname'),
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
        return (in_array($field, $this->updatedFields)) ? ' (to update)' : '';
    }

    public static function create(): self
    {
        return new self();
    }
}
