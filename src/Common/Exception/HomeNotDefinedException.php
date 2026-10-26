<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Exception;

use Exception;
use Throwable;

class HomeNotDefinedException extends Exception
{
    public function __construct(string $message = 'HOME environment variable not defined or empty', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
