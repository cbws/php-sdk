<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning;

use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Grpc\Longrunning\OperationsClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\Model\Operation;
use Cbws\Sdk\Common\Longrunning\V1alpha1\CancelOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\DeleteOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\GetOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\WaitOperationRequest;
use Fiber;
use Generator;
use Google\Protobuf\GPBEmpty;

class Operations
{
    public function __construct(protected OperationsClient $client) {}

    public function __destruct()
    {
        $this->client->close();
    }

    /**
     * @template TMetadata of MetadataInterface
     * @template TResponse of ResponseInterface
     *
     * @param class-string<TMetadata> $metadataType
     * @param class-string<TResponse> $responseType
     *
     * @return Operation<TMetadata, TResponse>
     *
     * @throws StatusException
     */
    public function getOperation(string $name, string $metadataType, string $responseType, ?GetOperationRequest $request = null): Operation
    {
        if ($request === null) {
            $request = new GetOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->client->GetOperation($request->toGrpc());

        /** @var GrpcOperation $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /** @phpstan-var Operation<TMetadata, TResponse> */
        return new Operation($data, $metadataType, $responseType, $this);
    }

    /**
     * Wait until the timeout or there has been progress in the operation.
     *
     * @template TMetadata of MetadataInterface
     * @template TResponse of ResponseInterface
     *
     * @param class-string<TMetadata> $metadataType
     * @param class-string<TResponse> $responseType
     *
     * @return Operation<TMetadata, TResponse>
     *
     * @throws StatusException
     */
    public function waitOperation(string $name, string $metadataType, string $responseType, int $timeoutMilliseconds = 1000, ?WaitOperationRequest $request = null): Operation
    {
        $request ??= new WaitOperationRequest();

        $request = $request->withName($name)->withTimeout($timeoutMilliseconds);
        $call = $this->client->WaitOperation($request->toGrpc());

        /** @var GrpcOperation $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /** @phpstan-var Operation<TMetadata, TResponse> */
        return new Operation($data, $metadataType, $responseType, $this);
    }

    /**
     * @throws StatusException
     */
    public function deleteOperation(string $name, ?DeleteOperationRequest $request = null): void
    {
        $request ??= new DeleteOperationRequest();

        $request = $request->withName($name);
        $call = $this->client->DeleteOperation($request->toGrpc());

        /** @var GPBEmpty $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }
    }

    /**
     * @throws StatusException
     */
    public function cancelOperation(string $name, ?CancelOperationRequest $request = null): void
    {
        if ($request === null) {
            $request = new CancelOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->client->CancelOperation($request->toGrpc());

        /** @var GPBEmpty $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }
    }

    /**
     * Poll the operation and yield changed operation changes until the operation finishes.
     *
     * @template TMetadata of MetadataInterface
     * @template TResponse of ResponseInterface
     *
     * @param class-string<TMetadata> $metadataType
     * @param class-string<TResponse> $responseType
     *
     * @throws StatusException
     */
    public function pollOperation(string $name, string $metadataType, string $responseType): Generator
    {
        $lastOperation = null;

        do {
            // Wait for operation to finish with default 1 second timeout
            $operation = $this->waitOperation($name, $metadataType, $responseType);

            // If operation hasn't changed don't yield changed operation to caller
            if ($operation === $lastOperation) {
                continue;
            }

            // Operation got updated in service, yield result
            yield $lastOperation = $operation;
        } while (!$operation->getDone());
    }

    /**
     * Wait until the operation has finished, either successfully or with an error.
     *
     * @template TMetadata of MetadataInterface
     * @template TResponse of ResponseInterface
     *
     * @param class-string<TMetadata> $metadataType
     * @param class-string<TResponse> $responseType
     *
     * @return Operation<TMetadata, TResponse>
     *
     * @throws StatusException
     */
    public function awaitOperation(string $name, string $metadataType, string $responseType): Operation
    {
        do {
            // Wait up to 10 seconds for each wait, however the backend will yield early when the operation has finished
            // so this is more efficient.
            $operation = $this->waitOperation($name, $metadataType, $responseType, 10000);
        } while (!$operation->getDone());

        /** @phpstan-var Operation<TMetadata, TResponse> */
        return $operation;
    }

    /**
     * Provider Fiber for waiting until the operation has finished, either successfully or with an error.
     *
     * @template TMetadata of MetadataInterface
     * @template TResponse of ResponseInterface
     *
     * @param class-string<TMetadata> $metadataType
     * @param class-string<TResponse> $responseType
     *
     * @return Fiber<?int, ?int, Operation<TMetadata, TResponse>, Operation<TMetadata, TResponse>>
     */
    public function fiberOperation(string $name, string $metadataType, string $responseType): Fiber
    {
        /** @phpstan-var Fiber<?int, ?int, Operation<TMetadata, TResponse>, Operation<TMetadata, TResponse>> */
        return new Fiber(function (?int $timeout = 5) use ($name, $metadataType, $responseType) {
            do {
                $operation = $this->waitOperation($name, $metadataType, $responseType, $timeout ?: 5);

                // We should see if we can have a way of providing a non-blocking way to implement fibers, doing get over
                // and over again would put a massive load on our service.
                // if ($timeout !== null) {
                //     $operation = $this->waitOperation($name, $metadataType, $responseType, $timeout);
                // } else {
                //     $operation = $this->getOperation($name, $metadataType, $responseType);
                // }

                /** @phpstan-var ?int $timeout */
                $timeout = Fiber::suspend($operation);
            } while (!$operation->getDone());

            return $operation;
        });
    }
}
