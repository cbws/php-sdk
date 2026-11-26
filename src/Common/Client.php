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

    /**
     * The gRPC metadata key that names the project a call is attributed to. Over HTTP it is the header
     * X-CBWS-Project-ID. Pass it in a call's metadata to attribute that one call to another project.
     */
    public const QUOTA_PROJECT_HEADER = 'x-cbws-project-id';

    /**
     * The environment variable holding the project for a client built without withQuotaProject().
     */
    public const QUOTA_PROJECT_ENV_VAR = 'CBWS_PROJECT_ID';

    protected string $project;

    /**
     * The project the client's calls are attributed to, see withQuotaProject().
     */
    protected ?string $quotaProject = null;

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

    /**
     * Attribute the client's calls to a project. A service uses it for a call that names no project itself, such as
     * getting a machine type, and ignores it for a call on a resource in a project, which belongs to that resource's
     * project. The caller needs permission to use the service in the project, or the service refuses the call.
     *
     * Without it the client takes the project from QUOTA_PROJECT_ENV_VAR, and otherwise uses its own project, the
     * same one the CBWS CLI attributes its calls to. Passing QUOTA_PROJECT_HEADER in a call's metadata overrides the
     * project for that call.
     *
     * It applies to the transports created after the call, so set it before the first request.
     *
     * @return static
     */
    public function withQuotaProject(?string $project): self
    {
        $this->quotaProject = $project;

        return $this;
    }

    public function getQuotaProject(): string
    {
        if ($this->quotaProject !== null && $this->quotaProject !== '') {
            return $this->quotaProject;
        }

        $project = getenv(self::QUOTA_PROJECT_ENV_VAR);

        if (is_string($project) && $project !== '') {
            return $project;
        }

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
        $quotaProject = $this->getQuotaProject();

        /**
         * Runs before every call. The token source caches the token and refreshes it when it expires, so this costs
         * nothing on most calls. It holds the token source rather than this client, so a transport never keeps its
         * client alive.
         *
         * The quota project is sent on every call rather than only on calls that name no project, because the service
         * ignores it for a call that does, and a list of such calls kept here would miss every call a service adds
         * later. A project already in the call's metadata wins.
         *
         * @param array<string, string[]> $metadata
         *
         * @return array<string, string[]>
         */
        $metadataProvider = static fn (array $metadata): array => $metadata + [
            'authorization' => ['Bearer '.$tokenSource->token()->getToken()],
            self::QUOTA_PROJECT_HEADER => [$quotaProject],
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
