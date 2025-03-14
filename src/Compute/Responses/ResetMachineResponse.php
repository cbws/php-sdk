<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\ResetMachineResponse as ResetMachineResponseGrpc;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<ResetMachineResponseGrpc>
 */
class ResetMachineResponse implements ResponseInterface
{
    protected ResetMachineResponseGrpc $object;

    /**
     * @param ResetMachineResponseGrpc $object
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
