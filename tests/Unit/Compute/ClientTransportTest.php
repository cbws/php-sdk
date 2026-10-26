<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Compute;

use Cbws\Grpc\Compute\V1alpha1\GetImageFromFamilyRequest as GrpcGetImageFromFamilyRequest;
use Cbws\Grpc\Compute\V1alpha1\Image as GrpcImage;
use Cbws\Grpc\Compute\V1alpha1\ListMachinesResponse as GrpcListMachinesResponse;
use Cbws\Grpc\Compute\V1alpha1\Machine as GrpcMachine;
use Cbws\Grpc\Transport\GrpcWebTransport;
use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Cbws\Sdk\Common\Client as BaseClient;
use Cbws\Sdk\Compute\Client;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class StaticTokenSource implements TokenSourceContract
{
    public function __construct(protected AccessTokenInterface $token) {}

    public function token(): AccessTokenInterface
    {
        return $this->token;
    }

    public function getProject(): ?string
    {
        return 'test-project';
    }
}

class StaticCredentials extends Credentials
{
    public function __construct(TokenSourceContract $tokenSource)
    {
        parent::__construct($tokenSource);
    }
}

/**
 * A compute client whose gRPC-Web transport sends its requests to a Guzzle mock handler.
 */
class MockHttpClient extends Client
{
    public function __construct(protected HttpClient $httpClient)
    {
        parent::__construct(null, new StaticCredentials(new StaticTokenSource(new AccessToken(['access_token' => 'test-token']))), null, self::TRANSPORT_GRPC_WEB);
    }

    public function getTransportOptions(): array
    {
        return parent::getTransportOptions() + ['http_client' => $this->httpClient];
    }
}

class ClientTransportTest extends TestCase
{
    public function test_forced_grpc_web_transport_is_shared_by_the_clients_of_the_endpoint(): void
    {
        $client = $this->client(BaseClient::TRANSPORT_GRPC_WEB);

        $transport = $client->getTransport();

        self::assertInstanceOf(GrpcWebTransport::class, $transport);
        self::assertSame($transport, $client->getClient()->getTransport());
    }

    public function test_unknown_transport_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client('http');
    }

    public function test_replaced_transport_is_shared(): void
    {
        $client = $this->client(BaseClient::TRANSPORT_GRPC_WEB);

        $failed = $client->getClient()->getTransport();
        $replacement = $client->createClient()->getTransport();

        self::assertNotSame($failed, $replacement);
        self::assertSame($replacement, $client->getTransport());

        // A second client of the same endpoint that held the failed transport moves to the replacement.
        self::assertSame($replacement, $client->replaceTransport($failed));
    }

    public function test_metadata_provider_adds_the_token(): void
    {
        $options = $this->client(null)->getTransportOptions();

        self::assertArrayNotHasKey('transport', $options);
        self::assertIsCallable($options['metadata_provider']);
        self::assertSame([
            'idempotency-key' => ['key'],
            'authorization' => ['Bearer test-token'],
        ], $options['metadata_provider'](['idempotency-key' => ['key']], '/cbws.compute.v1alpha1.ComputeService/ListMachines'));
    }

    public function test_list_machines_over_grpc_web(): void
    {
        $message = (new GrpcListMachinesResponse([
            'machines' => [new GrpcMachine(['name' => 'machine-1'])],
        ]))->serializeToString();
        $trailers = "grpc-status:0\r\ngrpc-message:\r\n";

        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['content-type' => 'application/grpc-web+proto'], pack('CN', 0, strlen($message)).$message.pack('CN', 0x80, strlen($trailers)).$trailers),
        ]));
        $handler->push(Middleware::history($history));

        $client = new MockHttpClient(new HttpClient(['handler' => $handler]));
        $response = $client->machines()->list();

        self::assertCount(1, $response->getMachines());
        self::assertSame('machine-1', $response->getMachines()[0]->getName());

        /** @var array<array{request: RequestInterface}> $history */
        self::assertCount(1, $history);
        $request = $history[0]['request'];
        self::assertSame('https://compute.cbws.cloud/cbws.compute.v1alpha1.ComputeService/ListMachines', (string) $request->getUri());
        self::assertSame('Bearer test-token', $request->getHeaderLine('authorization'));
        self::assertSame('application/grpc-web+proto', $request->getHeaderLine('content-type'));
        self::assertStringContainsString($client->getSdkUserAgent(), $request->getHeaderLine('user-agent'));
    }

    public function test_get_image_from_family_over_grpc_web(): void
    {
        $message = (new GrpcImage([
            'name' => 'projects/other-project/images/ubuntu-2404-lts-20260901',
            'parent' => 'projects/other-project',
            'family' => 'projects/other-project/families/ubuntu-2404-lts',
        ]))->serializeToString();
        $trailers = "grpc-status:0\r\ngrpc-message:\r\n";

        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, ['content-type' => 'application/grpc-web+proto'], pack('CN', 0, strlen($message)).$message.pack('CN', 0x80, strlen($trailers)).$trailers),
        ]));
        $handler->push(Middleware::history($history));

        $client = new MockHttpClient(new HttpClient(['handler' => $handler]));
        $image = $client->imageFamilies()->getImage(
            'ubuntu-2404-lts',
            'g4.2',
            (new \Cbws\Sdk\Compute\Requests\GetImageFromFamilyRequest())->withProject('other-project'),
        );

        self::assertSame('projects/other-project/images/ubuntu-2404-lts-20260901', $image->getName());
        self::assertSame('projects/other-project/families/ubuntu-2404-lts', $image->getFamily());

        /** @var array<array{request: RequestInterface}> $history */
        self::assertCount(1, $history);
        $request = $history[0]['request'];
        self::assertSame('https://compute.cbws.cloud/cbws.compute.v1alpha1.ComputeService/GetImageFromFamily', (string) $request->getUri());

        // The body is one gRPC-Web frame: a flag byte, a four-byte length and the message.
        $sent = new GrpcGetImageFromFamilyRequest();
        $sent->mergeFromString(substr((string) $request->getBody(), 5));
        self::assertSame('projects/other-project/families/ubuntu-2404-lts', $sent->getName());
        self::assertSame('machineTypes/g4.2', $sent->getMachineType());
    }

    protected function client(?string $transportType): Client
    {
        return new Client(null, new StaticCredentials(new StaticTokenSource(new AccessToken(['access_token' => 'test-token']))), null, $transportType);
    }
}
