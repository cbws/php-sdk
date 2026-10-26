<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute;

use Cbws\Grpc\Compute\V1alpha1\ComputeServiceClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\ReconnectProxy;
use Cbws\Sdk\Compute\Models\Image;
use Cbws\Sdk\Compute\Models\ImageFamily;
use Cbws\Sdk\Compute\Requests\GetImageFamilyRequest;
use Cbws\Sdk\Compute\Requests\GetImageFromFamilyRequest;
use Cbws\Sdk\Compute\Requests\ListImageFamiliesRequest;
use Cbws\Sdk\Compute\Responses\ListImageFamiliesResponse;
use Generator;
use Psr\Log\LoggerInterface;

/**
 * Reads the image families of a project. Requests use the client's project unless they name another one with
 * `withProject()`.
 */
class ImageFamilies
{
    public function __construct(protected Client $client, protected ?LoggerInterface $logger = null) {}

    /**
     * List image families in the project.
     *
     * @throws StatusException
     */
    public function list(ListImageFamiliesRequest $request = new ListImageFamiliesRequest()): ListImageFamiliesResponse
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ListImageFamiliesResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        if ($request->getParent() === null) {
            $request = $request->withProject($this->client->getProject());
        }

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\ListImageFamiliesResponse $data
         * @var object{ code: int, details: string }                  $status
         */
        [$data, $status] = $grpcClient->ListImageFamilies($request->toGrpc())->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new ListImageFamiliesResponse($data, $this);
    }

    /**
     * @yield ImageFamily
     *
     * @return Generator<ImageFamily>
     *
     * @throws StatusException
     */
    public function paginate(ListImageFamiliesRequest $request = new ListImageFamiliesRequest()): Generator
    {
        $nextPageToken = null;

        do {
            $request = $request->withPageToken($nextPageToken);

            $response = $this->list($request);

            foreach ($response->getImageFamilies() as $family) {
                yield $family;
            }

            $nextPageToken = $response->getNextPageToken();
        } while ($response->getNextPageToken() !== null);
    }

    /**
     * @throws StatusException
     */
    public function get(string $id, GetImageFamilyRequest $request = new GetImageFamilyRequest()): ImageFamily
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\ImageFamily> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $project = $request->getProject() ?? $this->client->getProject();
        $request = $request->withName("projects/{$project}/families/{$id}");

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\ImageFamily $data
         * @var object{ code: int, details: string }    $status
         */
        [$data, $status] = $grpcClient->GetImageFamily($request->toGrpc())->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new ImageFamily($data, $this);
    }

    /**
     * Get the image a machine of the given type boots from the family now. A family can hold different images for
     * different machine types.
     *
     * The machine type is a resource name, such as `zones/nl-ein-1/types/g4.2` or `machineTypes/g4.2`, or the id of a
     * machine type, such as `g4.2`, which is sent as `machineTypes/g4.2`.
     *
     * @throws StatusException
     */
    public function getImage(string $family, string $machineType, GetImageFromFamilyRequest $request = new GetImageFromFamilyRequest()): Image
    {
        /** @var ReconnectProxy<ComputeServiceClient, \Cbws\Grpc\Compute\V1alpha1\Image> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $project = $request->getProject() ?? $this->client->getProject();
        $request = $request
            ->withName("projects/{$project}/families/{$family}")
            ->withMachineType(str_contains($machineType, '/') ? $machineType : "machineTypes/{$machineType}");

        /**
         * @var \Cbws\Grpc\Compute\V1alpha1\Image    $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->GetImageFromFamily($request->toGrpc())->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        return new Image($data);
    }
}
