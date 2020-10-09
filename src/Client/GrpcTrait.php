<?php

namespace Cbws\API\Client;

use Grpc\CallCredentials;
use Grpc\ChannelCredentials;

trait GrpcTrait
{
    protected function getChannelCredentials(CallCredentials $callCredentials): ChannelCredentials
    {
        $ssl = ChannelCredentials::createSsl();
        return ChannelCredentials::createComposite(
            $ssl,
            $callCredentials
        );
    }
}
