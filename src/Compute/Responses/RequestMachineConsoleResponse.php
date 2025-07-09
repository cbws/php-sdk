<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\RequestMachineConsoleResponse as RequestMachineConsoleResponseGrpc;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<RequestMachineConsoleResponseGrpc>
 */
class RequestMachineConsoleResponse implements ResponseInterface
{
    protected RequestMachineConsoleResponseGrpc $object;

    /**
     * @param RequestMachineConsoleResponseGrpc $object
     */
    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function getUrl(): string
    {
        return $this->object->getUrl();
    }

    public function toGrpc(): Message
    {
        return $this->object;
    }

    public function __debugInfo()
    {
        return [
            'url' => $this->getUrl(),
        ];
    }
}
