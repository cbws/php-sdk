<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\Operation;
use Cbws\Sdk\Compute\Metadata\CreateMachineMetadata;
use Cbws\Sdk\Compute\Models\Machine;
use Cbws\Sdk\Compute\Requests\CreateMachineRequest;
use Cbws\Sdk\Compute\Requests\DeleteMachineRequest;
use Cbws\Sdk\Compute\Requests\GetMachineRequest;
use Cbws\Sdk\Compute\Requests\ListMachinesRequest;
use Cbws\Sdk\Compute\Requests\ResetMachineRequest;
use Cbws\Sdk\Compute\Requests\StartMachineRequest;
use Cbws\Sdk\Compute\Requests\StopMachineRequest;
use Cbws\Sdk\Compute\Responses\CreateMachineResponse;
use Cbws\Sdk\Compute\Responses\ListMachinesResponse;
use Generator;

class Machines
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List machines in the project.
     *
     * @throws StatusException
     */
    public function list(ListMachinesRequest $request = new ListMachinesRequest()): ListMachinesResponse
    {
        $request = $request->withProject($this->client->getProject());
        $call = $this->client->getClient()->ListMachines($request->toGrpc());

        /** @var GrpcListMachinesResponse $data */
        /** @var object{ code: int, details: string } $status */
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
    public function paginate(ListMachinesRequest $request = new ListMachinesRequest()): Generator
    {
        $nextPageToken = null;

        do {
            $request = $request->withPageToken($nextPageToken);

            $response = $this->list($request);

            foreach ($response->getMachines() as $machine) {
                yield $machine;
            }

            $nextPageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null);
    }

    /**
     * @return Operation<CreateMachineMetadata, CreateMachineResponse>
     *
     * @throws StatusException
     */
    public function create(string $id, Machine $machine, CreateMachineRequest $request = new CreateMachineRequest()): Operation
    {
        $request = $request
            ->withParent("projects/{$this->client->getProject()}")
            ->withID($id)
            ->withMachine($machine);
        $call = $this->client->getClient()->CreateMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

        /** @var GrpcOperation $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /**
         * @var Operation<CreateMachineMetadata, CreateMachineResponse> $operation
         */
        return new Operation($data);
    }

    /**
     * @throws StatusException
     */
    public function get(string $id, GetMachineRequest $request = new GetMachineRequest()): Machine
    {
        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        $call = $this->client->getClient()->GetMachine($request->toGrpc());

        /** @var GrpcMachine $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Machine($data);
    }

    /**
     * @throws StatusException
     */
    public function stop(string $id, StopMachineRequest $request = new StopMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        $call = $this->client->getClient()->StopMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

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
    public function startMachine(string $id, StartMachineRequest $request = new StartMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        $call = $this->client->getClient()->StartMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

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
    public function reset(string $id, ResetMachineRequest $request = new ResetMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        $call = $this->client->getClient()->ResetMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

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
    public function delete(string $id, DeleteMachineRequest $request = new DeleteMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        $call = $this->client->getClient()->DeleteMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

        /** @var GrpcOperation $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }
}
