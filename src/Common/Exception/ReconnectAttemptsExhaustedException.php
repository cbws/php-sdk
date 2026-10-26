<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Exception;

use Exception;
use Throwable;

class ReconnectAttemptsExhaustedException extends Exception
{
    public function __construct(int $attempts, ?Throwable $previous = null)
    {
        parent::__construct('Reconnect attempts exhausted after '.$attempts.' attempts', 0, $previous);
    }
}
