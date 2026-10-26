<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning;

use Google\Protobuf\Internal\Message;

/**
 * @implements MetadataInterface<Message>
 */
class UnknownMetadata implements MetadataInterface
{
    protected Message $object;

    public function __construct(Message $object)
    {
        $this->object = $object;
    }

    public function toGrpc(): Message
    {
        return $this->object;
    }
}
