<?php

declare(strict_types=1);

namespace Cbws\API\Client;

use Grpc\CallCredentials;
use Grpc\ChannelCredentials;

trait GrpcTrait
{
    protected function getChannelCredentials(CallCredentials $callCredentials): ChannelCredentials
    {
        return ChannelCredentials::createComposite(
            ChannelCredentials::createSsl(),
            $callCredentials,
        );
    }
}
