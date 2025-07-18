<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Sdk\Common\EmptyResponse;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\Model\Operation;
use Cbws\Sdk\Compute\Metadata\CreateMachineMetadata;
use Cbws\Sdk\Compute\Metadata\DeleteMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ReinstallMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ResetMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StartMachineMetadata;
use Cbws\Sdk\Compute\Metadata\StopMachineMetadata;
use Cbws\Sdk\Compute\Models\Machine;
use Cbws\Sdk\Compute\Requests\CreateMachineRequest;
use Cbws\Sdk\Compute\Requests\DeleteMachineRequest;
use Cbws\Sdk\Compute\Requests\GetMachineRequest;
use Cbws\Sdk\Compute\Requests\ListMachinesRequest;
use Cbws\Sdk\Compute\Requests\ReinstallMachineRequest;
use Cbws\Sdk\Compute\Requests\RequestMachineConsoleRequest;
use Cbws\Sdk\Compute\Requests\ResetMachineRequest;
use Cbws\Sdk\Compute\Requests\StartMachineRequest;
use Cbws\Sdk\Compute\Requests\StopMachineRequest;
use Cbws\Sdk\Compute\Responses\CreateMachineResponse;
use Cbws\Sdk\Compute\Responses\ListMachinesResponse;
use Cbws\Sdk\Compute\Responses\ReinstallMachineResponse;
use Cbws\Sdk\Compute\Responses\RequestMachineConsoleResponse;
use Cbws\Sdk\Compute\Responses\ResetMachineResponse;
use Cbws\Sdk\Compute\Responses\StartMachineResponse;
use Cbws\Sdk\Compute\Responses\StopMachineResponse;
use Generator;

class Machines
{
    public function __construct(protected Client $client) {}

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

        return new ListMachinesResponse($data, $this);
    }

    /**
     * @yield Machine
     *
     * @return Generator<Machine>
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

        /** @phpstan-var Operation<CreateMachineMetadata, CreateMachineResponse> */
        return new Operation($data, CreateMachineMetadata::class, CreateMachineResponse::class, $this->client->operations());
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

        return new Machine($data, $this);
    }

    /**
     * @return Operation<StopMachineMetadata, StopMachineResponse>
     *
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

        /** @phpstan-var Operation<StopMachineMetadata, StopMachineResponse> */
        return new Operation($data, StopMachineMetadata::class, StopMachineResponse::class, $this->client->operations());
    }

    /**
     * @return Operation<StartMachineMetadata, StartMachineResponse>
     *
     * @throws StatusException
     */
    public function start(string $id, StartMachineRequest $request = new StartMachineRequest()): Operation
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

        /** @phpstan-var Operation<StartMachineMetadata, StartMachineResponse> */
        return new Operation($data, StartMachineMetadata::class, StartMachineResponse::class, $this->client->operations());
    }

    /**
     * @return Operation<ResetMachineMetadata, ResetMachineResponse>
     *
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

        /** @phpstan-var Operation<ResetMachineMetadata, ResetMachineResponse> */
        return new Operation($data, ResetMachineMetadata::class, ResetMachineResponse::class, $this->client->operations());
    }

    /**
     * @return Operation<ReinstallMachineMetadata, ReinstallMachineResponse>
     *
     * @throws StatusException
     */
    public function reinstall(string $id, string $image, ReinstallMachineRequest $request = new ReinstallMachineRequest()): Operation
    {
        $request = $request
            ->withName("projects/{$this->client->getProject()}/machines/{$id}")
            ->withImage($image);
        $call = $this->client->getClient()->ReinstallMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

        /** @var GrpcOperation $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /** @phpstan-var Operation<ReinstallMachineMetadata, ReinstallMachineResponse> */
        return new Operation($data, ReinstallMachineMetadata::class, ReinstallMachineResponse::class, $this->client->operations());
    }

    /**
     * @return RequestMachineConsoleResponse
     *
     * @throws StatusException
     */
    public function requestConsole(string $id, RequestMachineConsoleRequest $request = new RequestMachineConsoleRequest()): RequestMachineConsoleResponse
    {
        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        $call = $this->client->getClient()->RequestMachineConsole($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ]);

        /** @var \Cbws\Grpc\Compute\V1alpha1\RequestMachineConsoleRequest $data */
        /** @var object{ code: int, details: string } $status */
        [$data, $status] = $call->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new RequestMachineConsoleResponse($data);
    }

    /**
     * @return Operation<DeleteMachineMetadata, EmptyResponse>
     *
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

        /** @phpstan-var Operation<DeleteMachineMetadata, EmptyResponse> */
        return new Operation($data, DeleteMachineMetadata::class, EmptyResponse::class, $this->client->operations());
    }
}
