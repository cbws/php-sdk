<?php

declare(strict_types=1);

namespace Cbws\Sdk\Iam;

use Cbws\Grpc\Iam\V1alpha1\IAMServiceClient;
use Cbws\Sdk\Common\Client as BaseClient;

/**
 * Client for the public IAM API.
 */
class Client extends BaseClient
{
    public const ENDPOINT = 'iam.cbws.cloud:443';

    protected ?IAMServiceClient $client = null;

    protected ?string $endpoint = null;

    public function serviceAccounts(): ServiceAccounts
    {
        return new ServiceAccounts($this, $this->logger);
    }

    /**
     * Call IAM at another endpoint, such as a stand-in in a lab. Null restores ENDPOINT.
     *
     * It applies to the transports created after the call, so set it before the first request.
     *
     * @return static
     */
    public function withEndpoint(?string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getClient(): IAMServiceClient
    {
        if ($this->client === null) {
            return $this->createClient();
        }

        return $this->client;
    }

    public function createClient(): IAMServiceClient
    {
        return $this->client = $this->instantiateNewClient();
    }

    protected function instantiateNewClient(): IAMServiceClient
    {
        return new IAMServiceClient($this->replaceTransport($this->client?->getTransport()));
    }

    public function getEndpoint(): string
    {
        return $this->endpoint ?? self::ENDPOINT;
    }
}
