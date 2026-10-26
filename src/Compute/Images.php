<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\ReconnectProxy;
use Cbws\Sdk\Compute\Models\Image;
use Cbws\Sdk\Compute\Requests\GetImageRequest;
use Cbws\Sdk\Compute\Requests\ListImagesRequest;
use Cbws\Sdk\Compute\Responses\ListImagesResponse;
use Generator;
use Psr\Log\LoggerInterface;

/**
 * Reads the operating system images of a project. Requests use the client's project unless they name another one with
 * `withProject()`.
 */
class Images
{
    public function __construct(protected Client $client, protected ?LoggerInterface $logger = null) {}

    /**
     * List images in the project.
     *
     * @throws StatusException
     */
    public function list(ListImagesRequest $request = new ListImagesRequest()): ListImagesResponse
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ListImagesResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        if ($request->getParent() === null) {
            $request = $request->withProject($this->client->getProject());
        }

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\ListImagesResponse $data
         * @var object{ code: int, details: string }           $status
         */
        [$data, $status] = $grpcClient->ListImages($request->toGrpc())->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new ListImagesResponse($data);
    }

    /**
     * @yield Image
     *
     * @return Generator<Image>
     *
     * @throws StatusException
     */
    public function paginate(ListImagesRequest $request = new ListImagesRequest()): Generator
    {
        $nextPageToken = null;

        do {
            $request = $request->withPageToken($nextPageToken);

            $response = $this->list($request);

            foreach ($response->getImages() as $image) {
                yield $image;
            }

            $nextPageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null);
    }

    /**
     * @throws StatusException
     */
    public function get(string $id, GetImageRequest $request = new GetImageRequest()): Image
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\Image> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $project = $request->getProject() ?? $this->client->getProject();
        $request = $request->withName("projects/{$project}/images/{$id}");

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\Image    $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->GetImage($request->toGrpc())->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Image($data);
    }
}
