<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\ImageFamily as ImageFamilyGrpc;
use Cbws\Grpc\Compute\V1alpha1\ListImageFamiliesResponse as ListImageFamiliesResponseGrpc;
use Cbws\Sdk\Compute\ImageFamilies;
use Cbws\Sdk\Compute\Models\ImageFamily;

class ListImageFamiliesResponse
{
    public function __construct(protected ListImageFamiliesResponseGrpc $object, protected ImageFamilies $client) {}

    /**
     * @return ImageFamily[]
     */
    public function getImageFamilies(): array
    {
        $families = [];

        /** @var \Google\Protobuf\RepeatedField<ImageFamilyGrpc> $grpcFamilies */
        $grpcFamilies = $this->object->getImageFamilies();

        foreach ($grpcFamilies as $family) {
            $families[] = new ImageFamily($family, $this->client);
        }

        return $families;
    }

    public function getNextPageToken(): ?string
    {
        return $this->object->getNextPageToken() === '' ? null : $this->object->getNextPageToken();
    }

    public function __debugInfo()
    {
        return [
            'imageFamilies' => $this->getImageFamilies(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
