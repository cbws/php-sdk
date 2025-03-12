<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\API\Client\AuthenticationTrait;
use Cbws\API\Client\GrpcTrait;
use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\Operation;
use Cbws\Sdk\Common\Longrunning\V1alpha1\OperationsTrait;
use Cbws\Sdk\Compute\Requests\CreateMachineRequest;
use Cbws\Sdk\Compute\Requests\DeleteMachineRequest;
use Cbws\Sdk\Compute\Requests\GetMachineRequest;
use Cbws\Sdk\Compute\Requests\ListMachinesRequest;
use Cbws\Sdk\Compute\Requests\ResetMachineRequest;
use Cbws\Sdk\Compute\Requests\StartMachineRequest;
use Cbws\Sdk\Compute\Requests\StopMachineRequest;
use Cbws\Sdk\Compute\Responses\ListMachinesResponse;
use Generator;

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
        $this->client?->close();
        $this->operationsClient?->close();
    }

    /**
     * List machines in the project.
     *
     * @throws StatusException
     */
    public function listMachines(ListMachinesRequest $request = new ListMachinesRequest()): ListMachinesResponse
    {
        $request = $request->withProject($this->project);
        $call = $this->getClient()->ListMachines($request->toGrpc());

        /** @var GrpcListMachinesResponse $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new ListMachinesResponse($data);
    }

    /**
     * @yield Machine
     *
     * @throws StatusException
     */
    public function paginateMachines(ListMachinesRequest $request = new ListMachinesRequest()): Generator
    {
        $nextPageToken = null;

        do {
            $request = $request->withPageToken($nextPageToken);

            $response = $this->listMachines($request);

            foreach ($response->getMachines() as $machine) {
                yield $machine;
            }

            $nextPageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null);
    }

    public function createMachine(string $id, Machine $machine, CreateMachineRequest $request = new CreateMachineRequest()): Operation
    {
        $request = $request
            ->withParent('projects/'.$this->project)
            ->withID($id)
            ->withMachine($machine);
        $call = $this->getClient()->CreateMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function getMachine(string $id, GetMachineRequest $request = new GetMachineRequest()): Machine
    {
        $request = $request->withName('projects/'.$this->project.'/machines/'.$id);
        $call = $this->getClient()->GetMachine($request->toGrpc());
        /** @var GrpcMachine $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Machine($data);
    }

    public function stopMachine(string $id, StopMachineRequest $request = new StopMachineRequest()): Operation
    {
        $request = $request->withName('projects/'.$this->project.'/machines/'.$id);
        $call = $this->getClient()->StopMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function startMachine(string $id, StartMachineRequest $request = new StartMachineRequest()): Operation
    {
        $request = $request->withName('projects/'.$this->project.'/machines/'.$id);
        $call = $this->getClient()->StartMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function resetMachine(string $id, ResetMachineRequest $request = new ResetMachineRequest()): Operation
    {
        $request = $request->withName('projects/'.$this->project.'/machines/'.$id);
        $call = $this->getClient()->ResetMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function deleteMachine(string $id, DeleteMachineRequest $request = new DeleteMachineRequest()): Operation
    {
        $request = $request->withName('projects/'.$this->project.'/machines/'.$id);
        $call = $this->getClient()->DeleteMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);
        /** @var GrpcOperation $data */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    protected function getClient(): ComputeServiceClient
    {
        if ($this->client !== null) {
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
