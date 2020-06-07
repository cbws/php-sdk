<?php

namespace Cbws\API\VPN\V1alpha1;

class ListProfilesResponse
{
    /**
     * @var \Cbws\VPN\GRPC\V1alpha1\ListProfilesResponse
     */
    protected $object;

    public function __construct(\Cbws\VPN\GRPC\V1alpha1\ListProfilesResponse $object)
    {
        $this->object = $object;
    }

    /**
     * @return Profile[]
     */
    public function getProfiles(): array
    {
        $profiles = [];
        foreach ($this->object->getProfiles() as $profile) {
            $profiles[] = new Profile($profile);
        }

        return $profiles;
    }

    public function getNextPageToken(): string
    {
        return $this->object->getNextPageToken();
    }

    public function __debugInfo()
    {
        return [
            'profiles' => $this->getProfiles(),
            'nextPageToken' => $this->getNextPageToken(),
        ];
    }
}
