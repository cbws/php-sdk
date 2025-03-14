<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common;

use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Internal\Message;

/**
 * @implements ResponseInterface<GPBEmpty>
 */
class EmptyResponse implements \Cbws\Sdk\Common\Longrunning\ResponseInterface
{
    public function __construct(protected Message $grpc) {}

    public function toGrpc(): Message
    {
        return $this->grpc;
    }
}
