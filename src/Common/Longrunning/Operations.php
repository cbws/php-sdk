<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning;

use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Grpc\Longrunning\OperationsClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\CancelOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\DeleteOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\GetOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\Operation;
use Cbws\Sdk\Common\Longrunning\V1alpha1\WaitOperationRequest;
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
     * @throws StatusException
     */
    public function getOperation(string $name, ?GetOperationRequest $request = null): Operation
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

        return new Operation($data);
    }

    /**
     * @throws StatusException
     */
    public function waitOperation(string $name, int $timeoutMilliseconds = 1000, ?WaitOperationRequest $request = null): Operation
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

        return new Operation($data);
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
     * @throws StatusException
     */
    public function pollOperation(string $name): Generator
    {
        $lastOperation = null;

        do {
            // Wait for operation to finish with default 1 second timeout
            $operation = $this->waitOperation($name);

            // If operation hasn't changed don't yield changed operation to caller
            if ($operation === $lastOperation) {
                continue;
            }

            // Operation got updated in service, yield result
            yield $lastOperation = $operation;
        } while (!$operation->getDone());
    }

    /**
     * @throws StatusException
     */
    public function awaitOperation(string $name): Operation
    {
        do {
            $operation = $this->getOperation($name);
            sleep(1);
        } while (!$operation->getDone());

        return $operation;
    }
}
