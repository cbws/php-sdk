<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\V1alpha1;

use Cbws\Grpc\Longrunning\OperationsClient;

trait OperationsTrait
{
    protected ?OperationsClient $operationsClient = null;

    protected function getOperationsClient(): OperationsClient
    {
        if ($this->operationsClient !== null) {
            return $this->operationsClient;
        }

        $channelCredentials = $this->getChannelCredentials($this->getCallCredentials());
        $this->operationsClient = new OperationsClient($this->getEndpoint(), [
            'credentials' => $channelCredentials,
        ]);

        return $this->operationsClient;
    }
}
