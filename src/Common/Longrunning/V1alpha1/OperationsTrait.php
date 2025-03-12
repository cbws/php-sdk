<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Grpc\Longrunning\OperationsClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Generator;
use Google\Protobuf\GPBEmpty;

trait OperationsTrait
{
    protected ?OperationsClient $operationsClient = null;

    public function getOperation(string $name, ?GetOperationRequest $request = null): Operation
    {
        if ($request === null) {
            $request = new GetOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->getOperationsClient()->GetOperation($request->toGrpc());
        /** @var GrpcOperation $data */
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
        $call = $this->getOperationsClient()->WaitOperation($request->toGrpc());
        /** @var GrpcOperation $data */
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
        $call = $this->getOperationsClient()->DeleteOperation($request->toGrpc());
        /** @var GPBEmpty $data */
        [/* $data */, $status] = $call->wait();

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
        $call = $this->getOperationsClient()->CancelOperation($request->toGrpc());
        /** @var GPBEmpty $data */
        [/* $data */, $status] = $call->wait();

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

    public function awaitOperation(string $name): Operation
    {
        do {
            $operation = $this->getOperation($name);
            sleep(1);
        } while (!$operation->getDone());

        return $operation;
    }

    protected function getOperationsClient(): OperationsClient
    {
        if ($this->operationsClient !== null) {
            return $this->operationsClient;
        }

        $channelCredentials = $this->getChannelCredentials($this->getCallCredentials());
        $this->operationsClient = new OperationsClient($this->getEndpoint(), [
            'credentials' => $channelCredentials,
        ]);

        return $this->operationsClient;
    }
}
