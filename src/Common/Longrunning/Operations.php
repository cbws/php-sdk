<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning;

use Cbws\Grpc\Longrunning\OperationsClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\GetOperationRequest;
use Cbws\Sdk\Common\Longrunning\V1alpha1\Operation;
use Cbws\Sdk\Common\Longrunning\V1alpha1\OperationsTrait;

class Operations
{
    use OperationsTrait {
        getOperation as _getOperation;
        awaitOperation as _awaitOperation;
    }

    protected ?OperationsClient $operationsClient = null;

    public function __construct(OperationsClient $operationsClient)
    {
        $this->operationsClient = $operationsClient;
    }

    /**
     * @throws StatusException
     */
    public function getOperation(string $name, ?GetOperationRequest $request = null): Operation
    {
        return $this->_getOperation($name, $request);
    }

    /**
     * @throws StatusException
     */
    public function awaitOperation(string $name): Operation
    {
        return $this->_awaitOperation($name);
    }
}
