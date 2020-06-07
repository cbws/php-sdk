<?php

namespace Cbws\API\VPN\V1alpha1;

class DeleteProfileBuilder
{
    protected $timeout;

    /**
     * @return int|null
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int|null $timeout
     * @return self
     */
    public function setTimeout($timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }
}
