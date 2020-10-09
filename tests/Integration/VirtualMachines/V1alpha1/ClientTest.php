<?php

namespace Cbws\API\Tests\Integration\VirtualMachines\V1alpha1;

use Cbws\API\VirtualMachines\V1alpha1\Client;
use Cbws\API\VirtualMachines\V1alpha1\Instance;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;

    public static function setUpBeforeClass(): void
    {
        $client = new Client('playground');
        self::cleanupInstances($client);

        $instance = Instance::create()
            ->withCPU(1)
            ->withMemory(512)
            ->withRootDisk(2)
            ->withHostname('php-cbws-test1.cbws.xyz');
        $operation = $client->createInstance(
            'php-cbws-test1',
            $instance
        );
        $client->awaitOperation($operation->getName());
    }

    public static function tearDownAfterClass(): void
    {
        $client = new Client('playground');
        self::cleanupInstances($client);
    }

    protected function setUp(): void
    {
        $this->client = new Client('playground');
    }


    protected function tearDown(): void
    {
        unset($this->client);
    }

    public function testListInstances()
    {
        $response = $this->client->listInstances();
        $this->assertIsArray($response->getInstances());
        $this->assertGreaterThan(0, count($response->getInstances()), 'Expected at least one instance');
    }

    public function testGetInstance()
    {
        $instance = $this->client->getInstance('php-cbws-test1');
        $this->assertEquals(2, $instance->getCPU(), 'Expected instance to have 1 CPU when doing get');
    }

    public function testUpdateInstance()
    {
        $operation = $this->client->updateInstance('php-cbws-test1', Instance::create()->withCPU(2));
        $this->assertStringContainsString('operations/', $operation->getName());
        $this->assertFalse($operation->getDone(), 'Expected operation to not be done');

        $operation = $this->client->awaitOperation($operation->getName());
        $this->assertTrue($operation->getDone(), 'Expected operation to be done');
        $this->assertNull($operation->getError(), 'Expected operation to not have any error');
        $this->assertInstanceOf(Instance::class, $operation->getResponse(), 'Expected operation to have instance as response');
        $this->assertEquals(2, $operation->getResponse()->getCPU(), 'Expected operation response instance to have 2 CPU');

        $instance = $this->client->getInstance('php-cbws-test1');
        $this->assertEquals(2, $instance->getCPU(), 'Expected instance to have 2 CPU when doing get');
    }

    protected static function cleanupInstances(Client $client)
    {
        $instances = $client->paginateInstances();
        /** @var Instance $instance */
        foreach ($instances as $instance) {
            if (strpos($instance->getID(), 'php-cbws-') !== 0) {
                continue;
            }

            echo 'Cleaning up instance ' . $instance->getName() . PHP_EOL;

            $operation = $client->deleteInstance($instance->getID());
            $client->awaitOperation($operation->getName());

            echo 'Cleaned up instance ' . $instance->getName() . PHP_EOL;
        }
    }
}
