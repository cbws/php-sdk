<?php

namespace Cbws\API\VPN\V1alpha1;

class CreateProfileBuilder
{
    protected $displayName;
    protected $timeout;

    /**
     * @return string|null
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * @param string|null $displayName
     * @return CreateProfileBuilder
     */
    public function setDisplayName($displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int|null $timeout
     * @return CreateProfileBuilder
     */
    public function setTimeout($timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }
}
