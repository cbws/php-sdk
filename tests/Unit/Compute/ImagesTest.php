<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Grpc\Compute\V1alpha1\GetImageRequest as GrpcGetImageRequest;
use Cbws\Grpc\Compute\V1alpha1\Image as GrpcImage;
use Cbws\Grpc\Compute\V1alpha1\ListImagesRequest as GrpcListImagesRequest;
use Cbws\Grpc\Compute\V1alpha1\ListImagesResponse as GrpcListImagesResponse;
use Cbws\Grpc\Transport\Status;
use Cbws\Grpc\Transport\UnaryCall;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Compute\Client;
use Cbws\Sdk\Compute\Images;
use Cbws\Sdk\Compute\Models\Image;
use Cbws\Sdk\Compute\Requests\GetImageRequest;
use Cbws\Sdk\Compute\Requests\ListImagesRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImagesTest extends TestCase
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

    public function test_list_images(): void
    {
        $grpcResponse = new GrpcListImagesResponse();
        $grpcResponse->setNextPageToken('next-page');
        $grpcResponse->setImages([new GrpcImage([
            'name' => 'projects/test-project/images/ubuntu-2404-lts-20260901',
            'parent' => 'projects/test-project',
            'family' => 'projects/test-project/families/ubuntu-2404-lts',
        ])]);

        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('ListImages')
            ->willReturnCallback(function (GrpcListImagesRequest $request) use (&$sent, $grpcResponse): UnaryCall {
                $sent[] = $request->getParent();

                return $this->call($grpcResponse);
            });

        $response = (new Images($this->client))->list();

        self::assertSame(['projects/test-project'], $sent);
        self::assertSame('next-page', $response->getNextPageToken());
        self::assertCount(1, $response->getImages());
        $image = $response->getImages()[0];
        self::assertInstanceOf(Image::class, $image);
        self::assertSame('projects/test-project/images/ubuntu-2404-lts-20260901', $image->getName());
        self::assertSame('ubuntu-2404-lts-20260901', $image->getID());
        self::assertSame('projects/test-project', $image->getParent());
        self::assertSame('projects/test-project/families/ubuntu-2404-lts', $image->getFamily());
    }

    public function test_list_images_of_another_project(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('ListImages')
            ->willReturnCallback(function (GrpcListImagesRequest $request) use (&$sent): UnaryCall {
                $sent[] = $request->getParent();

                return $this->call(new GrpcListImagesResponse());
            });

        $response = (new Images($this->client))->list((new ListImagesRequest())->withProject('other-project'));

        self::assertSame(['projects/other-project'], $sent);
        self::assertSame([], $response->getImages());
        self::assertNull($response->getNextPageToken());
    }

    public function test_paginate_images(): void
    {
        $pages = [
            '' => new GrpcListImagesResponse([
                'images' => [new GrpcImage(['name' => 'projects/test-project/images/image-1'])],
                'next_page_token' => 'page-2',
            ]),
            'page-2' => new GrpcListImagesResponse([
                'images' => [new GrpcImage(['name' => 'projects/test-project/images/image-2'])],
            ]),
        ];

        $tokens = [];
        $this->grpcClient->expects(self::exactly(2))
            ->method('ListImages')
            ->willReturnCallback(function (GrpcListImagesRequest $request) use (&$tokens, $pages): UnaryCall {
                $tokens[] = $request->getPageToken();
                self::assertSame('projects/test-project', $request->getParent());

                return $this->call($pages[$request->getPageToken()]);
            });

        $names = [];

        foreach ((new Images($this->client))->paginate() as $image) {
            $names[] = $image->getName();
        }

        self::assertSame(['', 'page-2'], $tokens);
        self::assertSame(['projects/test-project/images/image-1', 'projects/test-project/images/image-2'], $names);
    }

    public function test_get_image(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('GetImage')
            ->willReturnCallback(function (GrpcGetImageRequest $request) use (&$sent): UnaryCall {
                $sent[] = $request->getName();

                return $this->call(new GrpcImage(['name' => $request->getName()]));
            });

        $image = (new Images($this->client))->get('image-1');

        self::assertSame(['projects/test-project/images/image-1'], $sent);
        self::assertSame('projects/test-project/images/image-1', $image->getName());
        self::assertNull($image->getFamily());
    }

    public function test_get_image_of_another_project(): void
    {
        $sent = [];
        $this->grpcClient->expects(self::once())
            ->method('GetImage')
            ->willReturnCallback(function (GrpcGetImageRequest $request) use (&$sent): UnaryCall {
                $sent[] = $request->getName();

                return $this->call(new GrpcImage(['name' => $request->getName()]));
            });

        (new Images($this->client))->get('image-1', (new GetImageRequest())->withProject('other-project'));

        self::assertSame(['projects/other-project/images/image-1'], $sent);
    }

    public function test_get_image_throws_exception_on_error(): void
    {
        $this->expectException(StatusException::class);

        $call = $this->createMock(UnaryCall::class);
        $call->method('wait')->willReturn([null, new Status(Status::NOT_FOUND, 'not found')]);

        $this->grpcClient->expects(self::once())
            ->method('GetImage')
            ->willReturn($call);

        (new Images($this->client))->get('image-1');
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
