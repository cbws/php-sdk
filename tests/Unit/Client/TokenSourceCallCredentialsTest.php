<?php

namespace Cbws\API\Tests\Unit\Client;

use Cbws\API\Client\TokenSourceCallCredentials;
use Cbws\API\OAuth2\TokenSource;
use Grpc\CallCredentials;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use PHPUnit\Framework\TestCase;

class TokenSourceCallCredentialsTest extends TestCase
{
    public function testGetCallCredentials()
    {
        $tokenSourceCallCredentials = new TokenSourceCallCredentials(new TestTokenSource(new AccessToken([
            'access_token' => 'Test',
        ])));
        $callCredentials = $tokenSourceCallCredentials->getCallCredentials();
        $this->assertInstanceOf(CallCredentials::class, $callCredentials);
    }

    public function testCredentialsPlugin()
    {
        $tokenSourceCallCredentials = new TokenSourceCallCredentials(new TestTokenSource(new AccessToken([
            'access_token' => 'Test',
        ])));
        $headers = $tokenSourceCallCredentials->credentialsPlugin();
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('authorization', $headers);
        $this->assertEquals(['Bearer Test'], $headers['authorization']);
    }
}

class TestTokenSource implements TokenSource
{
    /**
     * @var AccessTokenInterface
     */
    protected $token;

    public function __construct(AccessTokenInterface $token)
    {
        $this->token = $token;
    }

    public function token(): AccessTokenInterface
    {
        return $this->token;
    }
}
