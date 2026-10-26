<?php

declare(strict_types=1);

namespace Cbws\Sdk\Common;

use Cbws\Grpc\Transport\Transport;
use Cbws\Grpc\Transport\Transports;
use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Cbws\Sdk\Common\Exception\FileNotFoundException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use Cbws\Sdk\Common\Longrunning\V1alpha1\OperationsTrait;
use Composer\InstalledVersions;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;

abstract class Client
{
    use OperationsTrait;

    /**
     * Native gRPC through the grpc extension.
     */
    public const TRANSPORT_GRPC = 'grpc';

    /**
     * gRPC-Web over HTTP, which needs no PHP extension.
     */
    public const TRANSPORT_GRPC_WEB = 'grpc-web';

    protected string $project;

    protected Credentials $credentials;

    protected ?LoggerInterface $logger;

    /**
     * The transport to use, or null to use native gRPC when the grpc extension is loaded and gRPC-Web otherwise.
     *
     * @var self::TRANSPORT_*|null
     */
    protected ?string $transportType = null;

    /**
     * The transport shared by every gRPC client of this endpoint.
     */
    protected ?Transport $transport = null;

    /**
     * The user provided client user-agent to append to the SDK user agent.
     */
    protected ?string $clientUserAgent = null;

    /**
     * @param string|null $transportType the transport to use, one of the TRANSPORT_* constants; see withTransportType()
     *
     * @throws FileNotFoundException|InvalidCredentialException|JsonException|ParseException
     */
    public function __construct(?string $project = null, ?Credentials $credentials = null, ?LoggerInterface $logger = null, ?string $transportType = null)
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
        $this->logger = $logger;
        $this->withTransportType($transportType);
    }

    public function __destruct()
    {
        $this->transport?->close();
    }

    public function getProject(): string
    {
        return $this->project;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getTokenSource(): TokenSourceContract
    {
        return $this->credentials->getTokenSource();
    }

    /**
     * @return self::TRANSPORT_*|null
     */
    public function getTransportType(): ?string
    {
        return $this->transportType;
    }

    /**
     * Force a transport: TRANSPORT_GRPC_WEB, for example, on a host where the grpc extension is installed but
     * unreliable. With null, the SDK uses native gRPC when the grpc extension is loaded and gRPC-Web otherwise.
     *
     * It applies to the transports created after the call, so set it before the first request.
     *
     * @return static
     */
    public function withTransportType(?string $transportType): self
    {
        if (!in_array($transportType, [null, self::TRANSPORT_GRPC, self::TRANSPORT_GRPC_WEB], true)) {
            throw new InvalidArgumentException(sprintf('Unknown transport "%s", expected "%s" or "%s"', $transportType, self::TRANSPORT_GRPC, self::TRANSPORT_GRPC_WEB));
        }

        $this->transportType = $transportType;

        return $this;
    }

    /**
     * The transport shared by every gRPC client of this endpoint.
     */
    public function getTransport(): Transport
    {
        return $this->transport ??= $this->createTransport();
    }

    /**
     * The transport for a new gRPC client that replaces one with the given transport. When that transport is the
     * shared one, its calls failed, so it is replaced; the other clients of this endpoint then move to the replacement
     * when they are replaced in turn.
     */
    public function replaceTransport(?Transport $failed): Transport
    {
        if ($failed !== null && $failed === $this->transport) {
            $this->transport = $this->createTransport();
        }

        return $this->getTransport();
    }

    protected function createTransport(): Transport
    {
        return Transports::create($this->getEndpoint(), $this->getTransportOptions());
    }

    /**
     * The options for the transport of this endpoint. Each transport ignores the options of the other, so these work
     * on hosts with and without the grpc extension.
     *
     * @return array<string, mixed>
     */
    public function getTransportOptions(): array
    {
        $tokenSource = $this->getTokenSource();

        /**
         * Runs before every call. The token source caches the token and refreshes it when it expires, so this costs
         * nothing on most calls. It holds the token source rather than this client, so a transport never keeps its
         * client alive.
         *
         * @param array<string, string[]> $metadata
         *
         * @return array<string, string[]>
         */
        $metadataProvider = static fn (array $metadata): array => $metadata + [
            'authorization' => ['Bearer '.$tokenSource->token()->getToken()],
        ];

        $options = [
            'metadata_provider' => $metadataProvider,

            // User agent to add to standard gRPC user agent
            'grpc.primary_user_agent' => $this->getUserAgent(),
        ];

        if ($this->transportType !== null) {
            $options['transport'] = $this->transportType;
        }

        return $options;
    }

    /**
     * Get the version of the installed CBWS SDK.
     */
    public function getSdkVersion(): ?string
    {
        $version = InstalledVersions::getVersion('cbws/sdk');

        if ($version) {
            return $version;
        }
        $version = InstalledVersions::getReference('cbws/sdk');

        if ($version) {
            return $version;
        }

        return null;
    }

    /**
     * Get the version of the installed CBWS protobufs.
     */
    public function getProtobufsVersion(): ?string
    {
        $version = InstalledVersions::getVersion('cbws/php-cbws-grpc');

        if ($version) {
            return $version;
        }
        $version = InstalledVersions::getReference('cbws/php-cbws-grpc');

        if ($version) {
            return $version;
        }

        return null;
    }

    public function getUserAgent(): string
    {
        $userAgent = $this->getSdkUserAgent();

        if ($this->getClientUserAgent()) {
            $userAgent = $this->getClientUserAgent().' '.$userAgent;
        }

        return $userAgent;
    }

    public function getSdkUserAgent(): string
    {
        return sprintf('cbws-php-sdk/%s cbws-php-protobufs/%s', $this->getSdkVersion(), $this->getProtobufsVersion());
    }

    public function getClientUserAgent(): ?string
    {
        return $this->clientUserAgent;
    }

    /**
     * @return static
     */
    public function withClientUserAgent(?string $clientUserAgent): self
    {
        $this->clientUserAgent = $clientUserAgent;

        return $this;
    }

    abstract public function getEndpoint(): string;
}
