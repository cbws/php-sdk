<?php

declare(strict_types=1);

namespace Cbws\Sdk\Tests\Integration\Compute;

use Cbws\Sdk\Compute\Client;
use Cbws\Sdk\Compute\Models\Machine;
use PHPUnit\Framework\TestCase;

class MachinesTest extends TestCase
{
    protected ?Client $client;

    public static function setUpBeforeClass(): void
    {
        $client = new Client();
        self::cleanup($client);

        $machine = (new Machine())
            ->withType('zones/nl-ein-1/types/g2.1');
        $operation = $client->machines()->create(
            'php-cbws-test1',
            $machine,
        )->await();

        if ($operation->getError() !== null) {
            echo 'Got error during create '.$operation->getError().PHP_EOL;

            throw $operation->getError();
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanup(new Client());
    }

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    protected function tearDown(): void
    {
        $this->client = null;
    }

    public function test_list_machines(): void
    {
        assert($this->client !== null);
        $response = $this->client->machines()->list();
        self::assertGreaterThan(0, count($response->getMachines()), 'Expected at least one machine');
    }

    public function test_get_machine(): void
    {
        assert($this->client !== null);
        $machine = $this->client->machines()->get('php-cbws-test1');
        self::assertEquals('zones/nl-ein-1/types/g2.1', $machine->getType(), 'Expected machine to have g2.1 type');
    }

    public function test_stop_machine(): void
    {
        assert($this->client !== null);
        $machine = $this->client->machines()->get('php-cbws-test1');
        $operation = $machine->stop()->await();

        if ($operation->getError() !== null) {
            throw $operation->getError();
        }
        self::assertTrue($operation->getDone());
    }

    public function test_start_machine(): void
    {
        assert($this->client !== null);
        $machine = $this->client->machines()->get('php-cbws-test1');
        $operation = $machine->start()->await();

        if ($operation->getError() !== null) {
            throw $operation->getError();
        }
        self::assertTrue($operation->getDone());
    }

    public function test_start_machine_fiber(): void
    {
        assert($this->client !== null);
        $machine = $this->client->machines()->get('php-cbws-test1');
        $fiber = $machine->start()->fiber();
        $fiber->start();

        // Wait until operation has finished
        while (!$fiber->isTerminated()) {
            $fiber->resume();
        }

        $operation = $fiber->getReturn();

        if ($operation->getError() !== null) {
            throw $operation->getError();
        }
        self::assertTrue($operation->getDone());
    }

    //    public function test_update_instance(): void
    //    {
    //        $operation = $this->client->updateInstance('php-cbws-test1', Instance::create()->withCPU(2));
    //        self::assertStringContainsString('operations/', $operation->getName());
    //        self::assertFalse($operation->getDone(), 'Expected operation to not be done');
    //
    //        $operation = $this->client->awaitOperation($operation->getName());
    //        self::assertTrue($operation->getDone(), 'Expected operation to be done');
    //        self::assertNull($operation->getError(), 'Expected operation to not have any error');
    //        self::assertInstanceOf(Instance::class, $operation->getResponse(), 'Expected operation to have instance as response');
    //        self::assertEquals(2, $operation->getResponse()->getCPU(), 'Expected operation response instance to have 2 CPU');
    //
    //        $instance = $this->client->getInstance('php-cbws-test1');
    //        self::assertEquals(2, $instance->getCPU(), 'Expected instance to have 2 CPU when doing get');
    //    }

    protected static function cleanup(Client $client): void
    {
        $machines = $client->machines()->paginate();

        foreach ($machines as $machine) {
            if (!str_starts_with($machine->getID(), 'php-cbws-')) {
                continue;
            }

            echo 'Cleaning up machine '.$machine->getName().PHP_EOL;

            $operation = $machine->delete()->await();

            if ($operation->getError() !== null) {
                echo 'Got error during delete '.$operation->getError().PHP_EOL;

                continue;
            }

            echo 'Cleaned up machine '.$machine->getName().PHP_EOL;
        }
    }
}
