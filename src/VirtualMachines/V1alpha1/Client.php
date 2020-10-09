<?php

namespace Cbws\API\VirtualMachines\V1alpha1;

use Cbws\API\Client\AuthenticationTrait;
use Cbws\API\Client\GrpcTrait;
use Cbws\API\Exception\StatusException;
use Cbws\API\Longrunning\V1alpha1\Operation;
use Cbws\API\Longrunning\V1alpha1\OperationsTrait;
use Cbws\VirtualMachines\Grpc\V1alpha1\Instance as GrpcInstance;
use Cbws\VirtualMachines\Grpc\V1alpha1\ListInstancesResponse as GrpcListInstancesResponse;
use Cbws\VirtualMachines\Grpc\V1alpha1\VirtualMachinesServiceClient;
use Google\LongRunning\Operation as GrpcOperation;

class Client
{
    use AuthenticationTrait;
    use GrpcTrait;
    use OperationsTrait;

    /**
     * @var VirtualMachinesServiceClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $project;

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
     * List virtual machine instances in the project
     *
     * @param ListInstancesRequest|null $request
     * @return ListInstancesResponse
     * @throws StatusException
     */
    public function listInstances(ListInstancesRequest $request = null): ListInstancesResponse
    {
        if (is_null($request)) {
            $request = new ListInstancesRequest();
        }

        $request = $request->withProject($this->project);
        $call = $this->getClient()->ListInstances($request->toGrpc());
        /** @var GrpcListInstancesResponse $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new ListInstancesResponse($data);
    }

    /**
     * @param ListInstancesRequest|null $request
     * @yield Instance
     * @return \Generator
     * @throws StatusException
     */
    public function paginateInstances(ListInstancesRequest $request = null)
    {
        if (is_null($request)) {
            $request = new ListInstancesRequest();
        }

        $nextPageToken = null;
        do {
            $request = $request->withPageToken($nextPageToken);

            $response = $this->listInstances($request);
            foreach ($response->getInstances() as $instance) {
                yield $instance;
            }

            $nextPageToken = $response->getNextPageToken();
        } while (!is_null($response->getNextPageToken()));
    }

    public function createInstance(string $id, Instance $instance, CreateInstanceRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new CreateInstanceRequest();
        }

        $request = $request
            ->withParent('projects/' . $this->project)
            ->withID($id)
            ->withInstance($instance);
        $call = $this->getClient()->CreateInstance($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function getInstance(string $id, GetInstanceRequest $request = null): Instance
    {
        if (is_null($request)) {
            $request = new GetInstanceRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/instances/' . $id);
        $call = $this->getClient()->GetInstance($request->toGrpc());
        /** @var GrpcInstance $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Instance($data);
    }

    public function stopInstance(string $id, GetInstanceRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new StopInstanceRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/instances/' . $id);
        $call = $this->getClient()->StopInstance($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function startInstance(string $id, StartInstanceRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new StartInstanceRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/instances/' . $id);
        $call = $this->getClient()->StartInstance($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function resetInstance(string $id, ResetInstanceRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new ResetInstanceRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/instances/' . $id);
        $call = $this->getClient()->ResetInstance($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function updateInstance(string $id, Instance $instance, UpdateInstanceRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new UpdateInstanceRequest();
        }

        $instance = $instance->withName('projects/' . $this->project . '/instances/' . $id);

        $request = $request->withInstance($instance)
            ->withFields(...$instance->updatedFields());
        $call = $this->getClient()->UpdateInstance($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    public function deleteInstance(string $id, DeleteInstanceRequest $request = null): Operation
    {
        if (is_null($request)) {
            $request = new DeleteInstanceRequest();
        }

        $request = $request->withName('projects/' . $this->project . '/instances/' . $id);
        $call = $this->getClient()->DeleteInstance($request->toGrpc());
        /** @var GrpcOperation $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Operation($data);
    }

    protected function getClient(): VirtualMachinesServiceClient
    {
        if (!is_null($this->client)) {
            return $this->client;
        }

        $channelCredentials = $this->getChannelCredentials($this->getCallCredentials());
        $this->client = new VirtualMachinesServiceClient($this->getEndpoint(), [
            'credentials' => $channelCredentials,
        ]);

        return $this->client;
    }

    protected function getEndpoint(): string
    {
        return 'vm.cbws.xyz:443';
    }
}
