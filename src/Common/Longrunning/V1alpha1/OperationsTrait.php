<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\OperationsClient;

trait OperationsTrait
{
    protected ?OperationsClient $operationsClient = null;

    protected function getOperationsClient(): OperationsClient
    {
        if ($this->operationsClient === null) {
            return $this->createOperationsClient();
        }

        return $this->operationsClient;
    }

    protected function createOperationsClient(): OperationsClient
    {
        return $this->operationsClient = new OperationsClient($this->replaceTransport($this->operationsClient?->getTransport()));
    }
}
