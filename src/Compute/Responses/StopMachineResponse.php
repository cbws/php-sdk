<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\StopMachineResponse as StopMachineResponseGrpc;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<StopMachineResponseGrpc>
 */
class StopMachineResponse implements ResponseInterface
{
    protected StopMachineResponseGrpc $object;

    /**
     * @param StopMachineResponseGrpc $object
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
