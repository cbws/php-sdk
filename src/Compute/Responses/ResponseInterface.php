<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Google\Protobuf\Internal\Message;

/**
 * @template T of Message
 */
interface ResponseInterface
{
    public function __construct(Message $grpc);

    public function toGrpc(): Message;
}
