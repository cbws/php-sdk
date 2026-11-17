<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Auth;

use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\TokenSources\ServiceAccountTokenSource;
use Cbws\Sdk\Auth\TokenSources\StaticTokenSource;
use Cbws\Sdk\Compute\Client;
use PHPUnit\Framework\TestCase;

class StaticAccessTokenTest extends TestCase
{
    /**
     * @var array<string, false|string>
     */
    private array $environment = [];

    protected function setUp(): void
    {
        foreach (['CBWS_ACCESS_TOKEN', 'CBWS_CREDENTIALS'] as $name) {
            $this->environment[$name] = getenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : "{$name}={$value}");
        }
    }

    public function test_from_access_token_serves_the_token(): void
    {
        $credentials = Credentials::fromAccessToken('at-1', 'project-1');

        self::assertInstanceOf(StaticTokenSource::class, $credentials->getTokenSource());
        self::assertSame('at-1', $credentials->getTokenSource()->token()->getToken());
        self::assertSame('project-1', $credentials->getTokenSource()->getProject());
    }

    public function test_a_client_sends_the_token(): void
    {
        $client = new Client(null, Credentials::fromAccessToken('at-1', 'project-1'));

        $metadataProvider = $client->getTransportOptions()['metadata_provider'];

        self::assertSame('project-1', $client->getProject());
        self::assertIsCallable($metadataProvider);
        self::assertSame(['authorization' => ['Bearer at-1']], $metadataProvider([]));
    }

    /**
     * The access token comes before every other source. CBWS_CREDENTIALS names a file that is not there, so reading it
     * would fail the lookup.
     */
    public function test_find_default_prefers_the_access_token_in_the_environment(): void
    {
        putenv('CBWS_ACCESS_TOKEN=at-env');
        putenv('CBWS_CREDENTIALS=/nonexistent/credentials.json');

        $credentials = Credentials::findDefault(['cbws.compute']);

        self::assertNotNull($credentials);
        self::assertInstanceOf(StaticTokenSource::class, $credentials->getTokenSource());
        self::assertSame('at-env', $credentials->getTokenSource()->token()->getToken());
        self::assertNull($credentials->getTokenSource()->getProject());
    }

    public function test_find_default_skips_an_empty_access_token(): void
    {
        $keyFile = tempnam(sys_get_temp_dir(), 'cbws-key-');
        self::assertIsString($keyFile);
        file_put_contents($keyFile, json_encode([
            'type' => 'service_account',
            'project_name' => 'project-1',
            'private_key_id' => 'key-1',
            'private_key' => 'not used here',
            'client_email' => 'sa@project-1.iam-sa.cbws.xyz',
            'client_id' => 'client-1',
        ], JSON_THROW_ON_ERROR));

        try {
            putenv('CBWS_ACCESS_TOKEN=');
            putenv("CBWS_CREDENTIALS={$keyFile}");

            $credentials = Credentials::findDefault();

            self::assertNotNull($credentials);
            self::assertInstanceOf(ServiceAccountTokenSource::class, $credentials->getTokenSource());
        } finally {
            unlink($keyFile);
        }
    }
}
