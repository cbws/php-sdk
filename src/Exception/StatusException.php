<?php

namespace Cbws\API\Exception;

use Exception;
use Google\Rpc\Code;
use Google\Rpc\Status;
use stdClass;
use Throwable;

class StatusException extends Exception
{
    public function __construct(Status $status, Throwable $previous = null)
    {
        $message = $status->getMessage();
        if (!in_array($status->getCode(), [Code::CANCELLED, Code::PERMISSION_DENIED])) {
            $message = Code::name($status->getCode()) . ': ' . $message;
        }

        parent::__construct($message, $status->getCode(), $previous);
    }

    public function getStatusCodeName(): string
    {
        return Code::name($this->getCode());
    }

    public static function fromStatus(stdClass $status): StatusException
    {
        return self::fromStatusMessage(new Status([
            'code' => $status->code,
            'message' => $status->details,
        ]));
    }

    public static function fromStatusMessage(Status $status): StatusException
    {
        switch ($status->getCode()) {
            case Code::CANCELLED:
                return new CancelledException($status);
            case Code::PERMISSION_DENIED:
                return new PermissionDeniedException($status);
        }

        return new self($status);
    }
}
