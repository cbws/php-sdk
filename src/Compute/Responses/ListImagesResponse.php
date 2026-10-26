<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Responses;

use Cbws\Grpc\Compute\V1alpha1\Image as ImageGrpc;
use Cbws\Grpc\Compute\V1alpha1\ListImagesResponse as ListImagesResponseGrpc;
use Cbws\Sdk\Compute\Models\Image;

class ListImagesResponse
{
    public function __construct(protected ListImagesResponseGrpc $object) {}

    /**
     * @return Image[]
     */
    public function getImages(): array
    {
        $images = [];

        /** @var \Google\Protobuf\RepeatedField<ImageGrpc> $grpcImages */
        $grpcImages = $this->object->getImages();

        foreach ($grpcImages as $image) {
            $images[] = new Image($image);
        }

        return $images;
    }

    public function getNextPageToken(): ?string
    {
        return $this->object->getNextPageToken() === '' ? null : $this->object->getNextPageToken();
    }

    public function __debugInfo()
    {
        return [
            'images' => $this->getImages(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
