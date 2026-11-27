<?php

declare(strict_types=1);

namespace Cbws\Sdk\Iam;

use Cbws\Grpc\Iam\V1alpha1\GenerateAccessTokenRequest;
use Cbws\Grpc\Iam\V1alpha1\GenerateAccessTokenResponse;
use Cbws\Grpc\Iam\V1alpha1\IAMServiceClient;
use Cbws\Sdk\Common\Exception\StatusException;
use Cbws\Sdk\Common\ReconnectProxy;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class ServiceAccounts
{
    public function __construct(protected Client $client, protected ?LoggerInterface $logger = null) {}

    /**
     * The resource name of a service account. The account is its email address or its unique id.
     */
    public static function name(string $project, string $account): string
    {
        return "projects/{$project}/serviceAccounts/{$account}";
    }

    /**
     * Mint an access token for a service account, with the scopes requested for it. The caller needs permission to
     * generate access tokens for the account.
     *
     * @param string   $serviceAccount the resource name of the account, as name() returns it
     * @param string[] $scopes
     *
     * @throws StatusException
     */
    public function generateAccessToken(string $serviceAccount, array $scopes): AccessTokenInterface
    {
        /** @var ReconnectProxy<IAMServiceClient, GenerateAccessTokenResponse> $grpcClient */
        $grpcClient = new ReconnectProxy($this->client->getClient(), $this->client->createClient(...), 3, $this->logger);

        $request = new GenerateAccessTokenRequest([
            'name' => $serviceAccount,
            'scope' => $scopes,
        ]);

        /**
         * @var GenerateAccessTokenResponse          $data
         * @var object{ code: int, details: string } $status
         */
        [$data, $status] = $grpcClient->GenerateAccessToken($request)->wait();

        if ($status->code !== 0) {
            throw StatusException::fromStatus($status);
        }

        // A token without an expiry would be reused until the process ends, so one is refused.
        $expireTime = $data->getExpireTime();

        if ($data->getAccessToken() === '' || $expireTime === null) {
            throw new UnexpectedValueException("IAM returned no access token or no expiry for {$serviceAccount}");
        }

        return new AccessToken([
            'access_token' => $data->getAccessToken(),
            'token_type' => 'Bearer',
            'expires' => (int) $expireTime->getSeconds(),
        ]);
    }
}
