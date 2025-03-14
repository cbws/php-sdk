<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\StartMachineResponse as StartMachineResponseGrpc;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<StartMachineResponseGrpc>
 */
class StartMachineResponse implements ResponseInterface
{
    protected StartMachineResponseGrpc $object;

    /**
     * @param StartMachineResponseGrpc $object
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
