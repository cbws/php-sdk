<?php

namespace Cbws\API\Longrunning\V1alpha1;

use Cbws\API\Exception\StatusException;
use Google\LongRunning\Operation as GrpcOperation;
use Google\LongRunning\OperationsClient;
use Google\Protobuf\GPBEmpty;

trait OperationsTrait
{
    /**
     * @var OperationsClient
     */
    protected $operationsClient;

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

    public function deleteOperation(string $name, DeleteOperationRequest $request = null)
    {
        if (is_null($request)) {
            $request = new DeleteOperationRequest();
        }

        $request = $request->withName($name);
        $call = $this->getOperationsClient()->Deleteperation($request->toGrpc());
        /** @var GPBEmpty $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }
    }

    public function cancelOperation(string $name, CancelOperationRequest $request = null)
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
