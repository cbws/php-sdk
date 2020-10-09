<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

class Instance
{
    const FIELD_HOSTNAME = 'hostname';
    const FIELD_CPU = 'cpu';
    const FIELD_MEMORY = 'memory';
    const FIELD_ROOT_DISK = 'root_disk';
    const FIELD_ADDRESS = 'address';
    const FIELD_SSH_KEYS = 'ssh_keys';
    const FIELD_STATE = 'state';
    const FIELD_LABELS = 'labels';

    /**
     * @var \Cbws\VirtualMachines\Grpc\V1alpha1\Instance
     */
    protected $object;

    protected $updatedFields = [];

    public function __construct(\Cbws\VirtualMachines\Grpc\V1alpha1\Instance $object = null)
    {
        if (is_null($object)) {
            $object = new \Cbws\VirtualMachines\Grpc\V1alpha1\Instance();
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

    public function getCPU(): ?int
    {
        return ($this->object->getCpu() != 0) ? $this->object->getCpu() : null;
    }

    public function withCPU(int $cpu = null): self
    {
        if ($cpu === $this->getCPU()) {
            return $this;
        }

        if (is_null($cpu)) {
            $cpu = 0;
        }

        $this->object->setCpu($cpu);
        $this->updatedFields[] = self::FIELD_CPU;

        return $this;
    }

    public function getMemory(): ?int
    {
        return ($this->object->getMemory() != 0) ? $this->object->getMemory() : null;
    }

    public function withMemory(int $memory = null): self
    {
        if ($memory === $this->getMemory()) {
            return $this;
        }

        if (is_null($memory)) {
            $memory = 0;
        }

        $this->object->setMemory($memory);
        $this->updatedFields[] = self::FIELD_MEMORY;

        return $this;
    }

    public function getRootDisk(): ?int
    {
        return ($this->object->getRootDisk() != 0) ? $this->object->getRootDisk() : null;
    }

    public function withRootDisk(int $rootDisk = null): self
    {
        if ($rootDisk === $this->getRootDisk()) {
            return $this;
        }

        if (is_null($rootDisk)) {
            $rootDisk = 0;
        }

        $this->object->setRootDisk($rootDisk);
        $this->updatedFields[] = self::FIELD_ROOT_DISK;

        return $this;
    }

    public function getAddress(): ?string
    {
        return ($this->object->getAddress() != "") ? $this->object->getAddress() : null;
    }

    public function withAddress(string $address = null): self
    {
        if ($address === $this->getAddress()) {
            return $this;
        }

        if (is_null($address)) {
            $address = '';
        }

        $this->object->setAddress($address);
        $this->updatedFields[] = self::FIELD_ADDRESS;

        return $this;
    }

    public function getSSHKeys(): array
    {
        return iterator_to_array($this->object->getSshKeys());
    }

    public function withSSHKeys(array $sshKeys = null): self
    {
        if ($sshKeys === $this->getSSHKeys()) {
            return $this;
        }

        if (is_null($sshKeys)) {
            $sshKeys = [];
        }

        $this->object->setSshKeys($sshKeys);
        $this->updatedFields[] = self::FIELD_SSH_KEYS;

        return $this;
    }

    public function getState(): int
    {
        return $this->object->getState();
    }

    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->object->getLabels() as $key => $value) {
            $labels[$key] = $value;
        }

        return $labels;
    }

    public function withLabels(array $labels = null): self
    {
        if ($labels === $this->getLabels()) {
            return $this;
        }

        if (is_null($labels)) {
            $labels = [];
        }

        $this->object->setLabels($labels);
        $this->updatedFields[] = self::FIELD_LABELS;

        return $this;
    }

    public function toGrpc(): \Cbws\VirtualMachines\Grpc\V1alpha1\Instance
    {
        return $this->object;
    }

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
            'cpu' => $this->getCPU() . $this->isUpdated('cpu'),
            'memory' => $this->getMemory() . $this->isUpdated('memory'),
            'rootDisk' => $this->getRootDisk() . $this->isUpdated('root_disk'),
            'address' => $this->getAddress() . $this->isUpdated('address'),
            'sshKeys' => $this->getSSHKeys(),
            'state' => $this->getState(),
            'labels' => $this->getLabels(),
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

    public static function create()
    {
        return new self();
    }
}
