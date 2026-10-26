<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Common;

use Cbws\Grpc\Compute\V1alpha1\StartMachineRequest;
use Cbws\Grpc\Transport\Status;
use Cbws\Grpc\Transport\UnaryCall;
use Cbws\Sdk\Common\Exception\ChannelClosedException;
use Cbws\Sdk\Common\Exception\ReconnectAttemptsExhaustedException;
use Cbws\Sdk\Common\ReconnectProxy;
use ColinODell\PsrTestLogger\TestLogger;
use Google\Protobuf\Any;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class GrpcClientMock
{
    /**
     * @return UnaryCall<Any>
     */
    public function someRemoteCall(StartMachineRequest $request): UnaryCall
    {
        throw new LogicException('Mocked in the tests');
    }

    public function close(): void {}
}

class ReconnectProxyTest extends TestCase
{
    public function test_successful_call_succeeds_on_first_try(): void
    {
        $logger = new TestLogger();

        // Mock the gRPC client
        $client = $this->createMock(GrpcClientMock::class);
        $rpcCall = $this->createMock(UnaryCall::class);
        $rpcCall->method('wait')->willReturn([new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), new Status(Status::OK)]);
        $rpcCall->method('getMetadata')->willReturn(['x-request-id' => ['abc']]);
        $client->method('someRemoteCall')->willReturn($rpcCall);

        $clientFactory = static fn () => $client;

        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 3, $logger);

        $call = $proxy->someRemoteCall(new StartMachineRequest());
        [$response, $status] = $call->wait();

        self::assertEquals(new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), $response);
        self::assertEquals(new Status(Status::OK), $status);
        self::assertSame(['x-request-id' => ['abc']], $call->getMetadata());

        self::assertTrue($logger->hasDebugThatContains('Starting gRPC call: {method}'));
        self::assertTrue($logger->hasInfo('gRPC call {method} succeeded'));
    }

    public function test_retry_succeeds_on_second_try(): void
    {
        // Mock the gRPC Unary Call object
        $successfulRpcCall = $this->createMock(UnaryCall::class);
        $successfulRpcCall->method('wait')->willReturn([new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), new Status(Status::OK)]);

        $failedRpcCall = $this->createMock(UnaryCall::class);
        $failedRpcCall->method('wait')->will(self::throwException(
            // See https://github.com/grpc/grpc/blob/master/src/php/ext/grpc/call.c#L228 for the gRPC internals
            // where they throw a zend_throw_exception(spl_ce_InvalidArgumentException,
            // "Call cannot be constructed from a closed Channel", 1 TSRMLS_CC);
            new InvalidArgumentException('Call cannot be constructed from a closed Channel'),
        ));

        // Mock the gRPC client
        $client = $this->createMock(GrpcClientMock::class);
        $client->expects(self::exactly(2))
            ->method('someRemoteCall')
            ->will(self::onConsecutiveCalls($failedRpcCall, $successfulRpcCall));
        $client->method('close');

        $clientFactory = static fn () => $client;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 2);

        [$response, $status] = $proxy->someRemoteCall(new StartMachineRequest())->wait();

        self::assertEquals(new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), $response);
        self::assertEquals(new Status(Status::OK), $status);
    }

    public function test_retry_fails_after_max_retries(): void
    {
        $this->expectException(ReconnectAttemptsExhaustedException::class);
        $this->expectExceptionMessage('Reconnect attempts exhausted after 3 attempts');

        // Mock the gRPC Unary Call object
        $failedRpcCall = $this->createMock(UnaryCall::class);
        $failedRpcCall->method('wait')->will(self::throwException(
            // See https://github.com/grpc/grpc/blob/master/src/php/ext/grpc/call.c#L228 for the gRPC internals
            // where they throw a zend_throw_exception(spl_ce_InvalidArgumentException,
            // "Call cannot be constructed from a closed Channel", 1 TSRMLS_CC);
            new InvalidArgumentException('Call cannot be constructed from a closed Channel'),
        ));

        // Mock the gRPC client
        $client = $this->createMock(GrpcClientMock::class);
        $client->method('someRemoteCall')->willReturn($failedRpcCall);
        $client->expects(self::exactly(3))->method('close');

        $clientFactory = static fn () => $client;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 3);
        $proxy->someRemoteCall(new StartMachineRequest())->wait();
    }

    public function test_retry_fails_after_max_retries_check_error(): void
    {
        $logger = new TestLogger();

        // Mock the gRPC Unary Call object
        $failedRpcCall = $this->createMock(UnaryCall::class);
        $failedRpcCall->method('wait')->will(self::throwException(
            // See https://github.com/grpc/grpc/blob/master/src/php/ext/grpc/call.c#L228 for the gRPC internals
            // where they throw a zend_throw_exception(spl_ce_InvalidArgumentException,
            // "Call cannot be constructed from a closed Channel", 1 TSRMLS_CC);
            new InvalidArgumentException('Call cannot be constructed from a closed Channel'),
        ));

        // Mock the gRPC client
        $client = $this->createMock(GrpcClientMock::class);
        $client->method('someRemoteCall')->willReturn($failedRpcCall);
        $client->expects(self::exactly(3))->method('close');

        $clientFactory = static fn () => $client;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 3, $logger);

        try {
            $proxy->someRemoteCall(new StartMachineRequest())->wait();
        } catch (ReconnectAttemptsExhaustedException $e) {
            // Check that the parent exception is a channel closed exception from our SDK
            self::assertInstanceOf(ChannelClosedException::class, $e->getPrevious());
            // Check that the exception in that is the original exception
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious()->getPrevious());
        }

        self::assertTrue($logger->hasNoticeThatContains('gRPC channel dead during {method}. Rebuilding client (Attempt {attempt} of {max}). Reason: {reason}'));
        self::assertTrue($logger->hasEmergencyThatContains('gRPC call {method} failed permanently after {attempts} attempts'));
    }

    public function test_non_retryable_error_status_does_not_retry(): void
    {
        // Mock the gRPC Unary Call object
        $failedRpcCall = $this->createMock(UnaryCall::class);
        $expectedStatus = new Status(Status::INTERNAL, 'Some other error');
        $failedRpcCall->method('wait')->willReturn([null, $expectedStatus]);

        // Mock the gRPC client
        $client = $this->createMock(GrpcClientMock::class);
        $client->expects(self::once())->method('someRemoteCall')->willReturn($failedRpcCall);
        $client->expects(self::never())->method('close');

        $clientFactory = static fn () => $client;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 3);

        [$data, $status] = $proxy->someRemoteCall(new StartMachineRequest())->wait();

        self::assertSame($expectedStatus, $status);
    }

    public function test_unavailable_status_rebuilds_client(): void
    {
        $logger = new TestLogger();

        $unavailableRpcCall = $this->createMock(UnaryCall::class);
        $unavailableRpcCall->method('wait')->willReturn([null, new Status(Status::UNAVAILABLE, 'Connection reset')]);

        $successfulRpcCall = $this->createMock(UnaryCall::class);
        $successfulRpcCall->method('wait')->willReturn([new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), new Status(Status::OK)]);

        $deadClient = $this->createMock(GrpcClientMock::class);
        $deadClient->expects(self::once())->method('someRemoteCall')->willReturn($unavailableRpcCall);
        $deadClient->expects(self::once())->method('close');

        $healthyClient = $this->createMock(GrpcClientMock::class);
        $healthyClient->expects(self::once())->method('someRemoteCall')->willReturn($successfulRpcCall);

        $clientFactory = static fn () => $healthyClient;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($deadClient, $clientFactory, 3, $logger);

        [$response, $status] = $proxy->someRemoteCall(new StartMachineRequest())->wait();

        self::assertEquals(new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), $response);
        self::assertSame(Status::OK, $status->code);
        self::assertTrue($logger->hasNoticeThatContains('gRPC channel dead during {method}. Rebuilding client (Attempt {attempt} of {max}). Reason: {reason}'));
    }

    public function test_unavailable_status_is_returned_after_max_retries(): void
    {
        $unavailableRpcCall = $this->createMock(UnaryCall::class);
        $unavailableRpcCall->method('wait')->willReturn([null, new Status(Status::UNAVAILABLE, 'Connection refused')]);

        $client = $this->createMock(GrpcClientMock::class);
        $client->expects(self::exactly(2))->method('someRemoteCall')->willReturn($unavailableRpcCall);
        $client->expects(self::once())->method('close');

        $clientFactory = static fn () => $client;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 2);

        [$response, $status] = $proxy->someRemoteCall(new StartMachineRequest())->wait();

        self::assertNull($response);
        self::assertSame(Status::UNAVAILABLE, $status->code);
        self::assertSame('Connection refused', $status->details);
    }

    public function test_retry_on_closed_channel_before_call(): void
    {
        // Mock the gRPC Unary Call object
        $successfulRpcCall = $this->createMock(UnaryCall::class);
        $successfulRpcCall->method('wait')->willReturn([new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), new Status(Status::OK)]);

        // Mock the gRPC client
        $client = $this->createMock(GrpcClientMock::class);
        $client->expects(self::exactly(2))
            ->method('someRemoteCall')
            ->will(self::onConsecutiveCalls(
                self::throwException(
                    // See https://github.com/grpc/grpc/blob/master/src/php/ext/grpc/call.c#L228 for the gRPC internals
                    // where they throw a zend_throw_exception(spl_ce_InvalidArgumentException,
                    // "Call cannot be constructed from a closed Channel", 1 TSRMLS_CC);
                    new InvalidArgumentException('Call cannot be constructed from a closed Channel'),
                ),
                $successfulRpcCall,
            ));
        $client->method('close');

        $clientFactory = static fn () => $client;
        /**
         * @var ReconnectProxy<GrpcClientMock, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $proxy
         */
        $proxy = new ReconnectProxy($client, $clientFactory, 2);

        [$response, $status] = $proxy->someRemoteCall(new StartMachineRequest())->wait();

        self::assertEquals(new \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse(), $response);
        self::assertEquals(new Status(Status::OK), $status);
    }
}
