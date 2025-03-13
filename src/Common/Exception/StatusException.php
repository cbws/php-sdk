<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Exception;

use Exception;
use Google\Rpc\Code;
use Google\Rpc\Status;
use stdClass;
use Throwable;

class StatusException extends Exception
{
    public function __construct(Status $status, ?Throwable $previous = null)
    {
        $message = $status->getMessage();

        if (!in_array($status->getCode(), [Code::CANCELLED, Code::PERMISSION_DENIED], true)) {
            $message = sprintf('%s: %s', Code::name($status->getCode()), $message);
        }

        parent::__construct($message, $status->getCode(), $previous);
    }

    public function getStatusCodeName(): string
    {
        return Code::name($this->getCode());
    }

    /**
     * @param object{ code: int, details: string } $status
     *
     * @return self
     */
    public static function fromStatus(object $status): self
    {
        return self::fromStatusMessage(new Status([
            'code' => $status->code,
            'message' => $status->details,
        ]));
    }

    public static function fromStatusMessage(Status $status): self
    {
        return match ($status->getCode()) {
            Code::CANCELLED => new CancelledException($status),
            Code::PERMISSION_DENIED => new PermissionDeniedException($status),
            default => new self($status),
        };
    }
}
