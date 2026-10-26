<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Sdk\Common\Client as BaseClient;
use Cbws\Sdk\Common\Longrunning\Operations;
use Cbws\Sdk\Common\Longrunning\V1alpha1\OperationsTrait;

class Client extends BaseClient
{
    use OperationsTrait;

    protected ?ComputeServiceClient $client = null;

    public function machines(): Machines
    {
        return new Machines($this, $this->logger);
    }

    public function images(): Images
    {
        return new Images($this, $this->logger);
    }

    public function imageFamilies(): ImageFamilies
    {
        return new ImageFamilies($this, $this->logger);
    }

    public function operations(): Operations
    {
        return new Operations($this->getOperationsClient(...), $this->createOperationsClient(...), $this->logger);
    }

    public function getClient(): ComputeServiceClient
    {
        if ($this->client === null) {
            return $this->createClient();
        }

        return $this->client;
    }

    public function createClient(): ComputeServiceClient
    {
        return $this->client = $this->instantiateNewClient();
    }

    protected function instantiateNewClient(): ComputeServiceClient
    {
        return new ComputeServiceClient($this->replaceTransport($this->client?->getTransport()));
    }

    /**
     * The channel arguments added here apply to native gRPC only; gRPC-Web ignores them.
     *
     * @return array<string, mixed>
     */
    public function getTransportOptions(): array
    {
        return parent::getTransportOptions() + [
            // Time in milliseconds before sending a keepalive ping if idle (e.g., 30 seconds)
            'grpc.keepalive_time_ms' => 10000,

            // Time to wait for the ping response before closing the connection (e.g., 10 seconds)
            'grpc.keepalive_timeout_ms' => 5000,

            // Send keepalive pings even if there are no active RPCs
            'grpc.keepalive_permit_without_calls' => 1,

            // Allow pings to be sent more frequently
            'grpc.http2.min_time_between_pings_ms' => 5000,
            'grpc.http2.min_ping_interval_without_data_ms' => 2500,
        ];
    }

    public function getEndpoint(): string
    {
        return 'compute.cbws.cloud:443';
    }
}
