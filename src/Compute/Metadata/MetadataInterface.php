<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Metadata;

use Google\Protobuf\Internal\Message;

/**
 * @template T of Message
 */
interface MetadataInterface
{
    /**
     * @param T $grpc
     */
    public function __construct(Message $grpc);

    /**
     * @return T
     */
    public function toGrpc(): Message;
}
