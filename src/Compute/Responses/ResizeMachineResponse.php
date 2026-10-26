<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\ResizeMachineResponse as ResizeMachineResponseGrpc;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<ResizeMachineResponseGrpc>
 */
class ResizeMachineResponse implements ResponseInterface
{
    protected ResizeMachineResponseGrpc $object;

    /**
     * @param ResizeMachineResponseGrpc $object
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
