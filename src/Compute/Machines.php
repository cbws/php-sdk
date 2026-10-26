<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Sdk\Common\EmptyResponse;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\Model\Operation;
use Cbws\Sdk\Common\ReconnectProxy;
use Cbws\Sdk\Compute\Metadata\CreateMachineMetadata;
use Cbws\Sdk\Compute\Metadata\DeleteMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ReinstallMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ResetMachineMetadata;
use Cbws\Sdk\Compute\Metadata\ResizeMachineMetadata;
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
use Cbws\Sdk\Compute\Requests\ResizeMachineRequest;
use Cbws\Sdk\Compute\Requests\StartMachineRequest;
use Cbws\Sdk\Compute\Requests\StopMachineRequest;
use Cbws\Sdk\Compute\Responses\CreateMachineResponse;
use Cbws\Sdk\Compute\Responses\ListMachinesResponse;
use Cbws\Sdk\Compute\Responses\ReinstallMachineResponse;
use Cbws\Sdk\Compute\Responses\RequestMachineConsoleResponse;
use Cbws\Sdk\Compute\Responses\ResetMachineResponse;
use Cbws\Sdk\Compute\Responses\ResizeMachineResponse;
use Cbws\Sdk\Compute\Responses\StartMachineResponse;
use Cbws\Sdk\Compute\Responses\StopMachineResponse;
use Generator;
use Google\Protobuf\Internal\Message;
use Psr\Log\LoggerInterface;

class Machines
{
    public function __construct(protected Client $client, protected ?LoggerInterface $logger = null) {}

    /**
     * List machines in the project.
     *
     * @throws StatusException
     */
    public function list(ListMachinesRequest $request = new ListMachinesRequest()): ListMachinesResponse
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withProject($this->client->getProject());
        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse $data
         * @var object{ code: int, details: string }             $status
         */
        [$data, $status] = $grpcClient->ListMachines($request->toGrpc())->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Longrunning\Operation> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request
            ->withParent("projects/{$this->client->getProject()}")
            ->withID($id)
            ->withMachine($machine);

        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->CreateMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\Machine> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\Machine  $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->GetMachine($request->toGrpc())->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\StopMachineResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");

        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->StopMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\StartMachineResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");

        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->StartMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ResetMachineResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");
        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->ResetMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ReinstallMachineResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request
            ->withName("projects/{$this->client->getProject()}/machines/{$id}")
            ->withImage($image);

        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->ReinstallMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /** @phpstan-var Operation<ReinstallMachineMetadata, ReinstallMachineResponse> */
        return new Operation($data, ReinstallMachineMetadata::class, ReinstallMachineResponse::class, $this->client->operations());
    }

    /**
     * @return Operation<ResizeMachineMetadata, ResizeMachineResponse>
     *
     * @throws StatusException
     */
    public function resize(string $id, string $type, ResizeMachineRequest $request = new ResizeMachineRequest()): Operation
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ResizeMachineResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request
            ->withName("projects/{$this->client->getProject()}/machines/{$id}")
            ->withType($type);

        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->ResizeMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /** @phpstan-var Operation<ResizeMachineMetadata, ResizeMachineResponse> */
        return new Operation($data, ResizeMachineMetadata::class, ResizeMachineResponse::class, $this->client->operations());
    }

    /**
     * @throws StatusException
     */
    public function requestConsole(string $id, RequestMachineConsoleRequest $request = new RequestMachineConsoleRequest()): RequestMachineConsoleResponse
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\RequestMachineConsoleResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\RequestMachineConsoleResponse $data
         * @var object{ code: int, details: string }                      $status
         */
        [$data, $status] = $grpcClient->RequestMachineConsole($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

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
        /** @var ReconnectProxy<ComputeServiceClient, Message> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = $request->withName("projects/{$this->client->getProject()}/machines/{$id}");

        /**
         * @var \Cbws\Grpc\Longrunning\Operation     $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->DeleteMachine($request->toGrpc(), [
            'Idempotency-Key' => [$request->getIdempotencyKey()->toString()],
        ])->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        /** @phpstan-var Operation<DeleteMachineMetadata, EmptyResponse> */
        return new Operation($data, DeleteMachineMetadata::class, EmptyResponse::class, $this->client->operations());
    }
}
