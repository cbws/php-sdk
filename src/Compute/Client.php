<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Sdk\Auth\Client\GrpcTrait;
use Cbws\Sdk\Common\Client as BaseClient;
use Cbws\Sdk\Common\Longrunning\V1alpha1\OperationsTrait;

class Client extends BaseClient
{
    use GrpcTrait;
    use OperationsTrait;

    protected ?ComputeServiceClient $client = null;

    public function __destruct()
    {
        parent::__destruct();
        $this->client?->close();
    }

    public function machines(): Machines
    {
        return new Machines($this);
    }

    public function getClient(): ComputeServiceClient
    {
        return $this->client ??= new ComputeServiceClient($this->getEndpoint(), [
            'credentials' => $this->getChannelCredentials($this->getCallCredentials()),
        ]);
    }

    public function getEndpoint(): string
    {
        return 'compute.cbws.cloud:443';
    }
}
