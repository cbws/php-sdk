<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Sdk\Auth\Client\GrpcTrait;
use Cbws\Sdk\Auth\Client\TokenSourceCallCredentials;
use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Cbws\Sdk\Common\Exception\FileNotFoundException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
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
use Grpc\CallCredentials;
use JsonException;

class Client
{
    use GrpcTrait;
    use OperationsTrait;

    protected string $project;

    protected Credentials $credentials;

    protected ?ComputeServiceClient $client = null;

    protected ?TokenSourceCallCredentials $tokenCallCredentials = null;

    /**
     * @throws FileNotFoundException|InvalidCredentialException|JsonException
     */
    public function __construct(?string $project = null, ?Credentials $credentials = null)
    {
        $credentials ??= Credentials::findDefault();

        if (!$credentials instanceof Credentials) {
            throw new InvalidCredentialException('Unable to determine a credentials.');
        }

        $project ??= $credentials->getTokenSource()->getProject();

        if (!is_string($project)) {
            throw new InvalidCredentialException('Unable to determine a project.');
        }

        $this->project = $project;
        $this->credentials = $credentials;
    }

    public function __destruct()
    {
        $this->client?->close();
        $this->operationsClient?->close();
    }

    protected function getTokenSource(): TokenSourceContract
    {
        return $this->credentials->getTokenSource();
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

    /**
     * @throws StatusException
     */
    public function createMachine(string $id, Machine $machine, CreateMachineRequest $request = new CreateMachineRequest()): Operation
    {
        $request = $request
            ->withParent("projects/{$this->project}")
            ->withID($id)
            ->withMachine($machine);
        $call = $this->getClient()->CreateMachine($request->toGrpc(), [
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
    public function getMachine(string $id, GetMachineRequest $request = new GetMachineRequest()): Machine
    {
        $request = $request->withName("projects/{$this->project}/machines/{$id}");
        $call = $this->getClient()->GetMachine($request->toGrpc());

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
    public function stopMachine(string $id, StopMachineRequest $request = new StopMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->project}/machines/{$id}");
        $call = $this->getClient()->StopMachine($request->toGrpc(), [
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
        $request = $request->withName("projects/{$this->project}/machines/{$id}");
        $call = $this->getClient()->StartMachine($request->toGrpc(), [
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
    public function resetMachine(string $id, ResetMachineRequest $request = new ResetMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->project}/machines/{$id}");
        $call = $this->getClient()->ResetMachine($request->toGrpc(), [
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
    public function deleteMachine(string $id, DeleteMachineRequest $request = new DeleteMachineRequest()): Operation
    {
        $request = $request->withName("projects/{$this->project}/machines/{$id}");
        $call = $this->getClient()->DeleteMachine($request->toGrpc(), [
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

    protected function getClient(): ComputeServiceClient
    {
        return $this->client ??= new ComputeServiceClient($this->getEndpoint(), [
            'credentials' => $this->getChannelCredentials($this->getCallCredentials()),
        ]);
    }

    protected function getCallCredentials(): CallCredentials
    {
        $this->tokenCallCredentials = new TokenSourceCallCredentials($this->getTokenSource());

        return $this->tokenCallCredentials->getCallCredentials();
    }

    protected function getEndpoint(): string
    {
        return 'compute.cbws.cloud:443';
    }
}
