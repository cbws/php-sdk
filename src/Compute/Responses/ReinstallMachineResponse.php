<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\ReinstallMachineResponse as ReinstallMachineResponseGrpc;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<ReinstallMachineResponseGrpc>
 */
class ReinstallMachineResponse implements ResponseInterface
{
    protected ReinstallMachineResponseGrpc $object;

    /**
     * @param ReinstallMachineResponseGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): Message
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [];
    }
}
