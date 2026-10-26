<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Longrunning\Operation as GrpcOperation;
use Cbws\Grpc\Transport\Status;
use Cbws\Grpc\Transport\UnaryCall;
use Cbws\Sdk\Compute\Client;
use Cbws\Sdk\Compute\Machines;
use Cbws\Sdk\Compute\Models\Machine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OperationsMock extends \Cbws\Sdk\Common\Longrunning\Operations
{
    public function __construct() {}

    public function __destruct() {}
}

class MachinesTest extends TestCase
{
    /**
     * @var Client|MockObject
     */
    private Client $client;

    /**
     * @var ComputeServiceClient|MockObject
     */
    private ComputeServiceClient $grpcClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grpcClient = $this->createMock(ComputeServiceClient::class);
        $this->client = $this->createMock(Client::class);
        $this->client->method('getClient')->willReturn($this->grpcClient);
        $this->client->method('getProject')->willReturn('test-project');
    }

    public function test_list_machines(): void
    {
        $grpcResponse = new GrpcListMachinesResponse();
        $grpcResponse->setNextPageToken('next-page');
        $machines = [new GrpcMachine(['name' => 'machine-1'])];
        $grpcResponse->setMachines($machines);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('ListMachines')
            ->willReturn($call);

        $machinesClient = new Machines($this->client);
        $response = $machinesClient->list();

        self::assertEquals('next-page', $response->getNextPageToken());
        self::assertCount(1, $response->getMachines());
        self::assertInstanceOf(Machine::class, $response->getMachines()[0]);
        self::assertEquals('machine-1', $response->getMachines()[0]->getName());
    }

    public function test_get_machine(): void
    {
        $grpcResponse = new GrpcMachine(['name' => 'machine-1']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('GetMachine')
            ->willReturn($call);

        $machinesClient = new Machines($this->client);
        $machine = $machinesClient->get('machine-1');

        self::assertEquals('machine-1', $machine->getName());
    }

    public function test_create_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/create-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('CreateMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->create('new-machine', new Machine());

        self::assertEquals('operations/create-machine-123', $operation->getName());
    }

    public function test_list_machines_throws_exception_on_error(): void
    {
        $this->expectException(\Cbws\Sdk\Common\Exception\StatusException::class);

        $status = new Status(Status::INTERNAL, 'test error');

        $call = $this->createMock(UnaryCall::class);
        $call->method('wait')->willReturn([null, $status]);

        $this->grpcClient->expects(self::once())
            ->method('ListMachines')
            ->willReturn($call);

        $machinesClient = new Machines($this->client);
        $machinesClient->list();
    }

    public function test_stop_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/stop-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('StopMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->stop('machine-1');

        self::assertEquals('operations/stop-machine-123', $operation->getName());
    }

    public function test_start_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/start-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('StartMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->start('machine-1');

        self::assertEquals('operations/start-machine-123', $operation->getName());
    }

    public function test_reset_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/reset-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('ResetMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->reset('machine-1');

        self::assertEquals('operations/reset-machine-123', $operation->getName());
    }

    public function test_reinstall_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/reinstall-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('ReinstallMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->reinstall('machine-1', 'image-1');

        self::assertEquals('operations/reinstall-machine-123', $operation->getName());
    }

    public function test_resize_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/resize-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('ResizeMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->resize('machine-1', 'type-1');

        self::assertEquals('operations/resize-machine-123', $operation->getName());
    }

    public function test_request_console(): void
    {
        $grpcResponse = new \Cbws\Grpc\Compute\V1alpha1\RequestMachineConsoleResponse(['url' => 'https://console']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('RequestMachineConsole')
            ->willReturn($call);

        $machinesClient = new Machines($this->client);
        $response = $machinesClient->requestConsole('machine-1');

        self::assertEquals('https://console', $response->getUrl());
    }

    public function test_delete_machine(): void
    {
        $grpcResponse = new GrpcOperation(['name' => 'operations/delete-machine-123']);

        $call = $this->createMock(UnaryCall::class);
        $status = new Status(Status::OK);
        $call->method('wait')->willReturn([$grpcResponse, $status]);

        $this->grpcClient->expects(self::once())
            ->method('DeleteMachine')
            ->willReturn($call);

        $operations = $this->createMock(OperationsMock::class);
        $this->client->method('operations')->willReturn($operations);

        $machinesClient = new Machines($this->client);
        $operation = $machinesClient->delete('machine-1');

        self::assertEquals('operations/delete-machine-123', $operation->getName());
    }
}
