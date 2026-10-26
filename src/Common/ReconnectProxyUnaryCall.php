<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common;

use Cbws\Grpc\Transport\Status;
use Cbws\Grpc\Transport\UnaryCall;
use Google\Protobuf\Internal\Message;

/**
 * A unary call that ReconnectProxy has already completed.
 *
 * @template T of Message
 *
 * @implements UnaryCall<T>
 */
class ReconnectProxyUnaryCall implements UnaryCall
{
    /**
     * @var ?T
     */
    private ?Message $message;

    private Status $status;

    /**
     * @var array<string, string[]>
     */
    private array $metadata;

    /**
     * @param ?T                      $message
     * @param array<string, string[]> $metadata the initial metadata of the call
     */
    public function __construct(?Message $message, Status $status, array $metadata = [])
    {
        $this->message = $message;
        $this->status = $status;
        $this->metadata = $metadata;
    }

    /**
     * @return array{0: T|null, 1: Status}
     */
    public function wait(): array
    {
        return [$this->message, $this->status];
    }

    /**
     * @return array<string, string[]>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * The call has completed, so there is nothing to cancel.
     */
    public function cancel(): void {}
}
