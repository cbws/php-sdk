<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common;

use Cbws\Grpc\Transport\Status;
use Cbws\Grpc\Transport\UnaryCall;
use Cbws\Sdk\Common\Exception\ChannelClosedException;
use Cbws\Sdk\Common\Exception\ReconnectAttemptsExhaustedException;
use Exception;
use Google\Protobuf\Internal\Message;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Makes unary calls on a generated gRPC client, and replaces the client when its connection is gone: when the call
 * throws, for example because the native gRPC channel was closed, or when it ends with UNAVAILABLE. The replacement
 * comes from the client factory, which gives it a new transport.
 *
 * @template T of object
 * @template M of Message
 *
 * @mixin T
 */
class ReconnectProxy
{
    /**
     * @var T
     */
    private object $client;

    /**
     * @var callable
     */
    private mixed $clientFactory;

    private int $maxRetries;

    private ?LoggerInterface $logger;

    /**
     * @param T            $currentClient The current active client that might have a closed channel
     * @param callable():T $clientFactory A closure that returns a fresh gRPC client instance
     * @param int          $maxRetries    How many times to retry upon failure
     */
    public function __construct(object $currentClient, callable $clientFactory, int $maxRetries = 2, ?LoggerInterface $logger = null)
    {
        $this->clientFactory = $clientFactory;
        $this->maxRetries = $maxRetries;
        $this->client = $currentClient;
        $this->logger = $logger;
    }

    /**
     * Intercepts all method calls made to the client.
     *
     * @param array<mixed> $arguments
     *
     * @return UnaryCall<M>
     *
     * @throws Exception
     * @throws Throwable
     */
    public function __call(string $name, array $arguments): UnaryCall
    {
        $attempt = 0;
        $lastException = null;

        $this->logger?->debug('Starting gRPC call: {method}', ['method' => $name]);

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $startTime = microtime(true);

                // Execute the RPC call on the actual client
                /** @var UnaryCall<M> $rpcCall */
                $rpcCall = $this->client->{$name}(...$arguments);

                // Note: This assumes Unary calls. See the note below about streaming.
                [$response, $status] = $rpcCall->wait();

                $duration = round((microtime(true) - $startTime) * 1000, 2);

                // The connection to the endpoint is gone; a new client with a new transport may reach it again. The last
                // attempt returns the status to the caller instead.
                if ($status->code === Status::UNAVAILABLE && $attempt < $this->maxRetries) {
                    $this->retry($name, $attempt, $status->details);

                    continue;
                }

                if ($status->code !== Status::OK) {
                    $this->logger?->warning('gRPC call {method} returned non-OK status', [
                        'method' => $name,
                        'code' => $status->code,
                        'details' => $status->details,
                        'duration_ms' => $duration,
                    ]);
                } else {
                    $this->logger?->info('gRPC call {method} succeeded', [
                        'method' => $name,
                        'duration_ms' => $duration,
                    ]);
                }

                return new ReconnectProxyUnaryCall($response, $status, $rpcCall->getMetadata());
            }
            // See https://github.com/grpc/grpc/blob/master/src/php/ext/grpc/call.c#L228 for the gRPC internals
            // where they throw a zend_throw_exception(spl_ce_InvalidArgumentException,
            // "Call cannot be constructed from a closed Channel", 1 TSRMLS_CC);
            catch (InvalidArgumentException $e) {
                if (str_contains($e->getMessage(), 'closed Channel')) {
                    $lastException = new ChannelClosedException($e);
                } else {
                    $this->logger?->error('gRPC Invalid Argument: {message}', ['message' => $e->getMessage()]);

                    $lastException = $e;
                }

                $this->retry($name, $attempt, $lastException->getMessage());
            }
            // Some other exception, I don't know when this could be called but we keep it just in case.
            catch (Throwable $e) {
                $lastException = $e;

                $this->retry($name, $attempt, $lastException->getMessage());
            }
        }

        $this->logger?->emergency('gRPC call {method} failed permanently after {attempts} attempts', [
            'method' => $name,
            'attempts' => $attempt,
            'exception' => $lastException?->getMessage(),
        ]);

        throw new ReconnectAttemptsExhaustedException($attempt, $lastException);
    }

    protected function retry(string $method, int $attempt, string $reason): void
    {
        $this->logger?->notice('gRPC channel dead during {method}. Rebuilding client (Attempt {attempt} of {max}). Reason: {reason}', [
            'method' => $method,
            'attempt' => $attempt,
            'max' => $this->maxRetries,
            'reason' => $reason,
        ]);

        // Discard the dead client and rebuild a fresh one
        if (method_exists($this->client, 'close')) {
            $this->client->close();
        }
        $this->client = ($this->clientFactory)();

        // Optional: add a tiny backoff here
        usleep(50000);
    }
}
