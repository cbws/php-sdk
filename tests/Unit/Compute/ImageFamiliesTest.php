<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Grpc\Compute\V1alpha1\GetImageFamilyRequest as GrpcGetImageFamilyRequest;
use Cbws\Grpc\Compute\V1alpha1\GetImageFromFamilyRequest as GrpcGetImageFromFamilyRequest;
use Cbws\Grpc\Compute\V1alpha1\Image as GrpcImage;
use Cbws\Grpc\Compute\V1alpha1\ImageFamily as GrpcImageFamily;
use Cbws\Grpc\Compute\V1alpha1\ListImageFamiliesRequest as GrpcListImageFamiliesRequest;
use Cbws\Grpc\Compute\V1alpha1\ListImageFamiliesResponse as GrpcListImageFamiliesResponse;
use Cbws\Grpc\Transport\Status;
use Cbws\Grpc\Transport\UnaryCall;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Compute\Client;
use Cbws\Sdk\Compute\ImageFamilies;
use Cbws\Sdk\Compute\Models\ImageFamily;
use Cbws\Sdk\Compute\Requests\GetImageFamilyRequest;
use Cbws\Sdk\Compute\Requests\GetImageFromFamilyRequest;
use Cbws\Sdk\Compute\Requests\ListImageFamiliesRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageFamiliesTest extends TestCase
{
    /**
     * @var Client|MockObject
     */
    private Client $client;

    /**
     * @var ComputeServiceClient|MockObject
     */
    private ComputeServiceClient $grpcClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grpcClient = $this->createMock(ComputeServiceClient::class);
        $this->client = $this->createMock(Client::class);
        $this->client->method('getClient')->willReturn($this->grpcClient);
        $this->client->method('getProject')->willReturn('test-project');
    }

    public function test_list_image_families(): void
    {
        $grpcResponse = new GrpcListImageFamiliesResponse([
            'image_families' => [new GrpcImageFamily([
                'name' => 'projects/test-project/families/ubuntu-2404-lts',
                'parent' => 'projects/test-project',
            ])],
            'next_page_token' => 'next-page',
        ]);

        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('ListImageFamilies')
            ->willReturnCallback(function (GrpcListImageFamiliesRequest $request) use (&$sent, $grpcResponse): UnaryCall {
                $sent[] = $request->getParent();

                return $this->call($grpcResponse);
            });

        $response = (new ImageFamilies($this->client))->list();

        self::assertSame(['projects/test-project'], $sent);
        self::assertSame('next-page', $response->getNextPageToken());
        self::assertCount(1, $response->getImageFamilies());
        $family = $response->getImageFamilies()[0];
        self::assertInstanceOf(ImageFamily::class, $family);
        self::assertSame('projects/test-project/families/ubuntu-2404-lts', $family->getName());
        self::assertSame('ubuntu-2404-lts', $family->getID());
        self::assertSame('projects/test-project', $family->getParent());
        self::assertSame('test-project', $family->getProject());
    }

    public function test_list_image_families_of_another_project(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('ListImageFamilies')
            ->willReturnCallback(function (GrpcListImageFamiliesRequest $request) use (&$sent): UnaryCall {
                $sent[] = $request->getParent();

                return $this->call(new GrpcListImageFamiliesResponse());
            });

        (new ImageFamilies($this->client))->list((new ListImageFamiliesRequest())->withProject('other-project'));

        self::assertSame(['projects/other-project'], $sent);
    }

    public function test_paginate_image_families(): void
    {
        $pages = [
            '' => new GrpcListImageFamiliesResponse([
                'image_families' => [new GrpcImageFamily(['name' => 'projects/other-project/families/family-1'])],
                'next_page_token' => 'page-2',
            ]),
            'page-2' => new GrpcListImageFamiliesResponse([
                'image_families' => [new GrpcImageFamily(['name' => 'projects/other-project/families/family-2'])],
            ]),
        ];

        $tokens = [];
        $this->grpcClient->expects(self::exactly(2))
            ->method('ListImageFamilies')
            ->willReturnCallback(function (GrpcListImageFamiliesRequest $request) use (&$tokens, $pages): UnaryCall {
                $tokens[] = $request->getPageToken();
                self::assertSame('projects/other-project', $request->getParent());

                return $this->call($pages[$request->getPageToken()]);
            });

        $names = [];

        foreach ((new ImageFamilies($this->client))->paginate((new ListImageFamiliesRequest())->withProject('other-project')) as $family) {
            $names[] = $family->getName();
        }

        self::assertSame(['', 'page-2'], $tokens);
        self::assertSame(['projects/other-project/families/family-1', 'projects/other-project/families/family-2'], $names);
    }

    public function test_get_image_family(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('GetImageFamily')
            ->willReturnCallback(function (GrpcGetImageFamilyRequest $request) use (&$sent): UnaryCall {
                $sent[] = $request->getName();

                return $this->call(new GrpcImageFamily(['name' => $request->getName()]));
            });

        $family = (new ImageFamilies($this->client))->get('ubuntu-2404-lts');

        self::assertSame(['projects/test-project/families/ubuntu-2404-lts'], $sent);
        self::assertSame('projects/test-project/families/ubuntu-2404-lts', $family->getName());
    }

    public function test_get_image_family_of_another_project(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('GetImageFamily')
            ->willReturnCallback(function (GrpcGetImageFamilyRequest $request) use (&$sent): UnaryCall {
                $sent[] = $request->getName();

                return $this->call(new GrpcImageFamily(['name' => $request->getName()]));
            });

        (new ImageFamilies($this->client))->get('ubuntu-2404-lts', (new GetImageFamilyRequest())->withProject('other-project'));

        self::assertSame(['projects/other-project/families/ubuntu-2404-lts'], $sent);
    }

    public function test_get_image_from_family(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::exactly(2))
            ->method('GetImageFromFamily')
            ->willReturnCallback(function (GrpcGetImageFromFamilyRequest $request) use (&$sent): UnaryCall {
                $sent[] = [$request->getName(), $request->getMachineType()];

                return $this->call(new GrpcImage([
                    'name' => 'projects/test-project/images/ubuntu-2404-lts-20260901',
                    'family' => $request->getName(),
                ]));
            });

        $families = new ImageFamilies($this->client);
        $image = $families->getImage('ubuntu-2404-lts', 'g4.2');
        $families->getImage('ubuntu-2404-lts', 'zones/nl-ein-1/types/g4.2');

        self::assertSame([
            ['projects/test-project/families/ubuntu-2404-lts', 'machineTypes/g4.2'],
            ['projects/test-project/families/ubuntu-2404-lts', 'zones/nl-ein-1/types/g4.2'],
        ], $sent);
        self::assertSame('projects/test-project/images/ubuntu-2404-lts-20260901', $image->getName());
        self::assertSame('projects/test-project/families/ubuntu-2404-lts', $image->getFamily());
    }

    public function test_get_image_from_family_of_another_project(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('GetImageFromFamily')
            ->willReturnCallback(function (GrpcGetImageFromFamilyRequest $request) use (&$sent): UnaryCall {
                $sent[] = [$request->getName(), $request->getMachineType()];

                return $this->call(new GrpcImage(['name' => 'projects/other-project/images/image-1']));
            });

        (new ImageFamilies($this->client))->getImage('ubuntu-2404-lts', 'machineTypes/g4.2', (new GetImageFromFamilyRequest())->withProject('other-project'));

        self::assertSame([['projects/other-project/families/ubuntu-2404-lts', 'machineTypes/g4.2']], $sent);
    }

    public function test_family_model_gets_its_image_from_its_own_project(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('GetImageFromFamily')
            ->willReturnCallback(function (GrpcGetImageFromFamilyRequest $request) use (&$sent): UnaryCall {
                $sent[] = [$request->getName(), $request->getMachineType()];

                return $this->call(new GrpcImage(['name' => 'projects/other-project/images/image-1']));
            });

        $families = new ImageFamilies($this->client);
        $family = new ImageFamily(new GrpcImageFamily(['name' => 'projects/other-project/families/ubuntu-2404-lts']), $families);
        $image = $family->getImage('g4.2');

        self::assertSame([['projects/other-project/families/ubuntu-2404-lts', 'machineTypes/g4.2']], $sent);
        self::assertSame('projects/other-project/images/image-1', $image->getName());
    }

    public function test_get_image_from_family_throws_exception_on_error(): void
    {
        $this->expectException(StatusException::class);

        $call = $this->createMock(UnaryCall::class);
        $call->method('wait')->willReturn([null, new Status(Status::INVALID_ARGUMENT, 'machine type is required')]);

        $this->grpcClient->expects(self::once())
            ->method('GetImageFromFamily')
            ->willReturn($call);

        (new ImageFamilies($this->client))->getImage('ubuntu-2404-lts', 'g4.2');
    }

    /**
     * @return MockObject&UnaryCall<\Google\Protobuf\Internal\Message>
     */
    private function call(\Google\Protobuf\Internal\Message $response): UnaryCall
    {
        $call = $this->createMock(UnaryCall::class);
        $call->method('wait')->willReturn([$response, new Status(Status::OK)]);

        return $call;
    }
}
