<?php

namespace Cbws\API\VPN\V1alpha1;

class Instance
{
    /**
     * @var \Cbws\VPN\GRPC\V1alpha1\Instance
     */
    protected $object;

    public function __construct(\Cbws\VPN\GRPC\V1alpha1\Instance $object)
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

    public function getDisplayName(): string
    {
        return $this->object->getDisplayName();
    }

    public function getIPv6Prefix(): string
    {
        return $this->object->getIpv6Prefix();
    }

    public function isIPv6DefaultGateway(): bool
    {
        return $this->object->getIpv6DefaultGateway();
    }

    public function isIPv4DefaultGateway(): bool
    {
        return $this->object->getIpv4DefaultGateway();
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'parent' => $this->getParent(),
            'displayName' => $this->getDisplayName(),
            'ipv6Prefix' => $this->getIPv6Prefix(),
            'ipv6DefaultGateway' => $this->isIPv6DefaultGateway(),
            'ipv4DefaultGateway' => $this->isIPv4DefaultGateway(),
        ];
    }
}
