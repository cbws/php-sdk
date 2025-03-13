<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common;

use Cbws\Sdk\Auth\Client\GrpcTrait;
use Cbws\Sdk\Auth\Client\TokenSourceCallCredentials;
use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Cbws\Sdk\Common\Exception\FileNotFoundException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\OperationsTrait;
use Grpc\CallCredentials;
use JsonException;

abstract class Client
{
    use GrpcTrait;
    use OperationsTrait;

    protected string $project;

    protected Credentials $credentials;

    protected ?TokenSourceCallCredentials $tokenCallCredentials = null;

    /**
     * @throws FileNotFoundException|InvalidCredentialException|JsonException
     */
    public function __construct(?string $project = null, ?Credentials $credentials = null)
    {
        $credentials ??= Credentials::findDefault();

        if (!$credentials instanceof Credentials) {
            throw new InvalidCredentialException('Unable to determine a credentials.');
        }

        $project ??= $credentials->getTokenSource()->getProject();

        if (!is_string($project)) {
            throw new InvalidCredentialException('Unable to determine a project.');
        }

        $this->project = $project;
        $this->credentials = $credentials;
    }

    public function __destruct()
    {
        $this->operationsClient?->close();
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getTokenSource(): TokenSourceContract
    {
        return $this->credentials->getTokenSource();
    }

    public function getCallCredentials(): CallCredentials
    {
        $this->tokenCallCredentials = new TokenSourceCallCredentials($this->getTokenSource());

        return $this->tokenCallCredentials->getCallCredentials();
    }

    abstract public function getEndpoint(): string;
}
