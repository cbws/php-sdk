<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Exception;

use Exception;
use Throwable;

class ChannelClosedException extends Exception
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('gRPC channel closed was caught', 0, $previous);
    }
}
