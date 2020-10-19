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
    const FIELD_CI_USERNAME = 'ci_username';
    const FIELD_CI_PASSWORD = 'ci_password';
    const FIELD_CI_USERDATA = 'ci_userdata';
    const FIELD_STATE = 'state';
    const FIELD_LABELS = 'labels';
    const FIELD_OPTIONS = 'options';

    const OPTION_REQUEST_IPV4 = 'request_ipv4';

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

    public function getAdditionalAddresses(): array
    {
        return iterator_to_array($this->object->getAdditionalAddress());
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

    public function getCIUsername(): ?string
    {
        return ($this->object->getCiUsername() != "") ? $this->object->getCiUsername() : null;
    }

    public function withCIUsername(string $username = null): self
    {
        if ($username === $this->getCIUsername()) {
            return $this;
        }

        if (is_null($username)) {
            $username = '';
        }

        $this->object->setCiUsername($username);
        $this->updatedFields[] = self::FIELD_CI_USERNAME;

        return $this;
    }

    public function getCIPassword(): ?string
    {
        return ($this->object->getCiPassword() != "") ? $this->object->getCiPassword() : null;
    }

    public function withCIPassword(string $password = null): self
    {
        if ($password === $this->getCIPassword()) {
            return $this;
        }

        if (is_null($password)) {
            $password = '';
        }

        $this->object->setCiPassword($password);
        $this->updatedFields[] = self::FIELD_CI_PASSWORD;

        return $this;
    }

    public function getCIUserdata(): ?string
    {
        return ($this->object->getCiUserdata() != "") ? $this->object->getCiUserdata() : null;
    }

    public function withCIUserdata(string $userdata = null): self
    {
        if ($userdata === $this->getCIUserdata()) {
            return $this;
        }

        if (is_null($userdata)) {
            $userdata = '';
        }

        $this->object->setCiUserdata($userdata);
        $this->updatedFields[] = self::FIELD_CI_USERDATA;

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

    public function getOptions(): array
    {
        $options = [];
        foreach ($this->object->getOptions() as $key => $value) {
            $options[$key] = $value;
        }

        return $options;
    }

    public function withOptions(array $options = null): self
    {
        if ($options === $this->getOptions()) {
            return $this;
        }

        if (is_null($options)) {
            $options = [];
        }

        $this->object->setOptions($options);
        $this->updatedFields[] = self::FIELD_OPTIONS;

        return $this;
    }

    public function getRequestIPv4(): ?bool
    {
        $options = $this->getOptions();
        if (!isset($options[self::OPTION_REQUEST_IPV4])) {
            return null;
        }

        return $options[self::OPTION_REQUEST_IPV4];
    }

    public function withRequestIPv4(bool $request = true): self
    {
        $options = $this->getOptions();
        $options[self::OPTION_REQUEST_IPV4] = ($request) ? 'true' : 'false';

        return $this->withOptions($options);
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
            'additionalAddresses' => $this->getAdditionalAddresses(),
            'sshKeys' => $this->getSSHKeys(),
            'ciUsername' => $this->getCIUsername() . $this->isUpdated(self::FIELD_CI_USERNAME),
            'ciPassword' => $this->getCIPassword(),
            'ciUserdata' => $this->getCIUserdata() . $this->isUpdated(self::FIELD_CI_USERDATA),
            'state' => $this->getState(),
            'labels' => $this->getLabels(),
            'options' => $this->getOptions(),
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
