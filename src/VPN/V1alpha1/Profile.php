<?php

namespace Cbws\API\VPN\V1alpha1;

class Profile
{
    /**
     * @var \Cbws\VPN\GRPC\V1alpha1\Profile
     */
    protected $object;

    public function __construct(\Cbws\VPN\GRPC\V1alpha1\Profile $object)
    {
        $this->object = $object;
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getParent(): string
    {
        return $this->object->getParent();
    }

    public function getDisplayName(): ?string
    {
        return ($this->object->getDisplayName()) ? $this->object->getDisplayName() : null;
    }

    public function getIPv6Prefix(): string
    {
        return $this->object->getIpv6Prefix();
    }

    public function getIPv6Address(): string
    {
        return $this->object->getIpv6Address();
    }

    public function getIPv4Address(): string
    {
        return $this->object->getIpv4Address();
    }

    public function getProfileArchive(): string
    {
        return $this->object->getProfileArchive();
    }

    public function getCACertificate(): string
    {
        return $this->object->getCaCertificate();
    }

    public function getCertificate(): string
    {
        return $this->object->getCertificate();
    }

    public function getPrivateKey(): string
    {
        return $this->object->getPrivateKey();
    }

    public function __debugInfo()
    {
        var_dump($this->object->getParent());

        return [
            'name' => $this->getName(),
            'parent' => $this->getParent(),
            'displayName' => $this->getDisplayName(),
            'ipv6Prefix' => $this->getIPv6Prefix(),
            'ipv6Address' => $this->getIPv6Address(),
            'ipv4Address' => $this->getIPv4Address(),
            'profileArchive' => ($this->getProfileArchive()) ? '*ZIP file*' : '',
            'caCertificate' => $this->getCACertificate(),
            'certificate' => $this->getCertificate(),
            'privateKey' => $this->getPrivateKey(),
        ];
    }
}
