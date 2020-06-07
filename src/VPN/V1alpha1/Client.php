<?php

namespace Cbws\API\VPN\V1alpha1;

use Cbws\API\OAuth2\Credentials;
use Cbws\API\OAuth2\TokenSource;
use Cbws\VPN\GRPC\V1alpha1\CreateProfileRequest;
use Cbws\VPN\GRPC\V1alpha1\DeleteProfileRequest;
use Cbws\VPN\GRPC\V1alpha1\ListInstancesRequest;
use Cbws\VPN\GRPC\V1alpha1\ListInstancesResponse;
use Cbws\VPN\GRPC\V1alpha1\ListProfilesRequest;
use Cbws\VPN\GRPC\V1alpha1\Profile;
use Cbws\VPN\GRPC\V1alpha1\VPNServiceClient;
use Google\Protobuf\GPBEmpty;
use Grpc\CallCredentials;
use Grpc\ChannelCredentials;

class Client
{
    /**
     * @var VPNServiceClient
     */
    protected $client;

    /**
     * @var TokenSource
     */
    protected $tokenSource;

    public function __construct()
    {
        $this->tokenSource = Credentials::FindDefault([])->getTokenSource();

        $ssl = ChannelCredentials::createSsl();
        $channelCredentials = ChannelCredentials::createComposite(
            $ssl,
            CallCredentials::createFromPlugin([$this, 'credentialsUpdate'])
        );
        $this->client = new VPNServiceClient('vpn.cbws.xyz:443', [
            'credentials' => $channelCredentials,
        ]);
    }

    public function credentialsUpdate()
    {
        return [
            'authorization' => ['Bearer ' . $this->tokenSource->token()->getToken()],
        ];
    }

    public function listInstances($parent): \Cbws\API\VPN\V1alpha1\ListInstancesResponse
    {
        $call = $this->client->ListInstances(new ListInstancesRequest([
            'parent' => $parent,
        ]));
        /** @var ListInstancesResponse $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw new \Exception($status->details);
        }

        return new \Cbws\API\VPN\V1alpha1\ListInstancesResponse($data);
    }

    public function listProfiles($parent): \Cbws\API\VPN\V1alpha1\ListProfilesResponse
    {
        $call = $this->client->ListProfiles(new ListProfilesRequest([
            'parent' => $parent,
        ]));
        /** @var \Cbws\VPN\GRPC\V1alpha1\ListProfilesResponse $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw new \Exception($status->details);
        }

        return new \Cbws\API\VPN\V1alpha1\ListProfilesResponse($data);
    }

    public function createProfile(string $parent, string $name, CreateProfileBuilder $builder = null): \Cbws\API\VPN\V1alpha1\Profile
    {
        if (is_null($builder)) {
            $builder = new CreateProfileBuilder();
        }
        $call = $this->client->CreateProfile(new CreateProfileRequest([
            'parent' => $parent,
            'name' => $name,
            'profile' => new \Cbws\VPN\GRPC\V1alpha1\Profile([
                'display_name' => $builder->getDisplayName() ?? '',
            ]),
        ]), [], [
            'timeout' => $builder->getTimeout(),
        ]);
        /** @var Profile $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw new \Exception($status->details);
        }

        return new \Cbws\API\VPN\V1alpha1\Profile($data);
    }

    public function deleteProfile(string $name, DeleteProfileBuilder $builder = null)
    {
        if (is_null($builder)) {
            $builder = new DeleteProfileBuilder();
        }
        $call = $this->client->DeleteProfile(new DeleteProfileRequest([
            'name' => $name,
        ]), [], [
            'timeout' => $builder->getTimeout(),
        ]);
        /** @var GPBEmpty $data */
        list($data, $status) = $call->wait();
        if ($status->code !== 0) {
            throw new \Exception($status->details);
        }
    }
}
