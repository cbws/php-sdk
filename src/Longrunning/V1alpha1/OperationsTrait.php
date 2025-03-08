<?php

namespace Cbws\Sdk\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Grpc\Longrunning\OperationsClient;
use Cbws\Sdk\Exception\StatusException;
use Google\Protobuf\GPBEmpty;

trait OperationsTrait
{
    protected ?OperationsClient $operationsClient = null;

    public function getOperation(string $name, GetOperationRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new GetOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->getOperationsClient()->GetOperation($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function waitOperation(string $name, int $timeoutMilliseconds = 1000, WaitOperationRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new WaitOperationRequest();
        }

        $request = $request->withName($name)->withTimeout($timeoutMilliseconds);
        $call = $this->getOperationsClient()->WaitOperation($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function deleteOperation(string $name, DeleteOperationRequest $request = null): void
    {
        if (is_null($request)) {
            $request = new DeleteOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->getOperationsClient()->DeleteOperation($request->toGrpc());
        /** @var GPBEmpty $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }
    }

    public function cancelOperation(string $name, CancelOperationRequest $request = null): void
    {
        if (is_null($request)) {
            $request = new CancelOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->getOperationsClient()->CancelOperation($request->toGrpc());
        /** @var GPBEmpty $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }
    }

    /**
     * Poll the operation and yield changed operation changes until the operation finishes.
     *
     * @param string $name
     * @return \Generator
     * @throws StatusException
     */
    public function pollOperation(string $name): \Generator
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
        if (!is_null($this->operationsClient)) {
            return $this->operationsClient;
        }

        $channelCredentials = $this->getChannelCredentials($this->getCallCredentials());
        $this->operationsClient = new OperationsClient($this->getEndpoint(), [
            'credentials' => $channelCredentials,
        ]);

        return $this->operationsClient;
    }
}
