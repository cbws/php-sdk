<?php

namespace Cbws\Sdk\Compute\V1alpha1;

use Cbws\API\Client\AuthenticationTrait;
use Cbws\API\Client\GrpcTrait;
use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Sdk\Exception\StatusException;
use Cbws\Sdk\Longrunning\V1alpha1\Operation;
use Cbws\Sdk\Longrunning\V1alpha1\OperationsTrait;

class Client
{
    use AuthenticationTrait;
    use GrpcTrait;
    use OperationsTrait;

    protected ?ComputeServiceClient $client = null;

    protected string $project;

    public function __construct(string $project)
    {
        $this->project = $project;

        $this->tokenSource = $this->getDefaultTokenSource();
    }

    public function __destruct()
    {
        if (!is_null($this->client)) {
            $this->client->close();
        }
        if (!is_null($this->operationsClient)) {
            $this->operationsClient->close();
        }
    }

    /**
     * List machines in the project
     *
     * @param ListMachinesRequest|null $request
     * @return ListMachinesResponse
     * @throws StatusException
     */
    public function listMachines(ListMachinesRequest $request = null): ListMachinesResponse
    {
        if (is_null($request)) {
            $request = new ListMachinesRequest();
        }

        $request = $request->withProject($this->project);
        $call = $this->getClient()->ListMachines($request->toGrpc());
        /** @var GrpcListMachinesResponse $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new ListMachinesResponse($data);
    }

    /**
     * @param ListMachinesRequest|null $request
     * @yield Machine
     * @return \Generator
     * @throws StatusException
     */
    public function paginateMachines(ListMachinesRequest $request = null)
    {
        if (is_null($request)) {
            $request = new ListMachinesRequest();
        }

        $nextPageToken = null;
        do {
            $request = $request->withPageToken($nextPageToken);

            $response = $this->listMachines($request);
            foreach ($response->getMachines() as $machine) {
                yield $machine;
            }

            $nextPageToken = $response->getNextPageToken();
        } while (!is_null($response->getNextPageToken()));
    }

    public function createMachine(string $id, Machine $machine, CreateMachineRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new CreateMachineRequest();
        }

        $request = $request
            ->withParent('projects/' . $this->project)
            ->withID($id)
            ->withMachine($machine);
        $call = $this->getClient()->CreateMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function getMachine(string $id, GetMachineRequest $request = null): Machine
    {
        if (is_null($request)) {
            $request = new GetMachineRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/machines/' . $id);
        $call = $this->getClient()->GetMachine($request->toGrpc());
        /** @var GrpcMachine $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Machine($data);
    }

    public function stopMachine(string $id, StopMachineRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new StopMachineRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/machines/' . $id);
        $call = $this->getClient()->StopMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function startMachine(string $id, StartMachineRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new StartMachineRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/machines/' . $id);
        $call = $this->getClient()->StartMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function resetMachine(string $id, ResetMachineRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new ResetMachineRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/machines/' . $id);
        $call = $this->getClient()->ResetMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function deleteMachine(string $id, DeleteMachineRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new DeleteMachineRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/machines/' . $id);
        $call = $this->getClient()->DeleteMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    protected function getClient(): ComputeServiceClient
    {
        if (!is_null($this->client)) {
            return $this->client;
        }

        $channelCredentials = $this->getChannelCredentials($this->getCallCredentials());
        $this->client = new ComputeServiceClient($this->getEndpoint(), [
            'credentials' => $channelCredentials,
        ]);

        return $this->client;
    }

    protected function getEndpoint(): string
    {
        return 'compute.cbws.cloud:443';
    }
}

