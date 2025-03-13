<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Client;

use Cbws\Sdk\Auth\Client\TokenSourceCallCredentials;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Grpc\CallCredentials;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use PHPUnit\Framework\TestCase;

class TokenSourceCallCredentialsTest extends TestCase
{
    public function test_get_call_credentials(): void
    {
        $tokenSourceCallCredentials = new TokenSourceCallCredentials(new TestTokenSource(new AccessToken([
            'access_token' => 'Test',
        ])));
        $callCredentials = $tokenSourceCallCredentials->getCallCredentials();
        self::assertInstanceOf(CallCredentials::class, $callCredentials);
    }

    public function test_credentials_plugin(): void
    {
        $tokenSourceCallCredentials = new TokenSourceCallCredentials(new TestTokenSource(new AccessToken([
            'access_token' => 'Test',
        ])));
        $headers = $tokenSourceCallCredentials->credentialsPlugin();
        self::assertIsArray($headers);
        self::assertArrayHasKey('authorization', $headers);
        self::assertEquals(['Bearer Test'], $headers['authorization']);
    }
}

class TestTokenSource implements TokenSourceContract
{
    protected AccessTokenInterface $token;

    public function __construct(AccessTokenInterface $token)
    {
        $this->token = $token;
    }

    public function token(): AccessTokenInterface
    {
        return $this->token;
    }

    public function getProject(): ?string
    {
        return 'my-awesome-project';
    }
}
