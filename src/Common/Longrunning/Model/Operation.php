<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common\Longrunning\Model;

use Cbws\Grpc\Longrunning\Operation as OperationGrpc;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\Longrunning\MetadataInterface;
use Cbws\Sdk\Common\Longrunning\Operations;
use Cbws\Sdk\Common\Longrunning\ResponseInterface;
use Exception;

/**
 * @template TMetadata of MetadataInterface
 * @template TResponse of ResponseInterface
 */
class Operation
{
    /**
     * @param class-string<TMetadata> $metadataType
     * @param class-string<TResponse> $responseType
     */
    public function __construct(protected OperationGrpc $object, protected string $metadataType, protected string $responseType, protected Operations $client) {}

    public function getName(): string
    {
        return $this->object->getName();
    }

    /**
     * @return ?TMetadata
     *
     * @throws Exception
     */
    public function getMetadata(): ?MetadataInterface
    {
        $metadata = $this->object->getMetadata();

        if ($metadata === null) {
            return null;
        }

        return new $this->metadataType($metadata->unpack());
    }

    public function getDone(): bool
    {
        return $this->object->getDone();
    }

    /**
     * @return ?TResponse
     *
     * @throws Exception
     */
    public function getResponse(): ?ResponseInterface
    {
        $response = $this->object->getResponse();

        if ($response === null) {
            return null;
        }

        return new $this->responseType($response->unpack());
    }

    public function getError(): ?StatusException
    {
        if ($this->object->getError() === null) {
            return null;
        }

        return StatusException::fromStatusMessage($this->object->getError());
    }

    /**
     *  Wait until the operation has finished, either successfully or with an error.
     *
     * @return Operation<TMetadata, TResponse>
     *
     * @throws StatusException
     */
    public function await(): self
    {
        return $this->client->awaitOperation($this->getName(), $this->metadataType, $this->responseType);
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->getName(),
            'metadata' => $this->getMetadata(),
            'done' => $this->getDone(),
            'error' => $this->getError()?->getMessage(),
            'response' => $this->getResponse(),
        ];
    }
}
