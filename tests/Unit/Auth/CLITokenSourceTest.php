<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Auth;

use Cbws\Sdk\Auth\TokenSources\CLITokenSource;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Reading the token out of a config file written by the cbws CLI.
 */
class CLITokenSourceTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private string $file;

    protected function setUp(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'cbws-cli-');

        if ($file === false) {
            self::fail('Could not create a temporary config file');
        }

        $this->file = $file;
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    /**
     * The config as the CLI writes it: the expiry is an unquoted timestamp, which YAML reads as a
     * timestamp rather than a string.
     */
    private function source(string $expiryLine): CLITokenSource
    {
        file_put_contents($this->file, <<<YAML
            project_name: example
            oauth_token:
                accesstoken: the-access-token
                tokentype: Bearer
                refreshtoken: the-refresh-token
            {$expiryLine}
                expiresin: 3600
            YAML);

        return new CLITokenSource($this->file);
    }

    #[DataProvider('provideA_token_that_has_not_expired_is_used_as_isCases')]
    public function test_a_token_that_has_not_expired_is_used_as_is(string $expiryLine): void
    {
        $token = $this->source($expiryLine)->token();

        self::assertSame('the-access-token', $token->getToken());
        self::assertGreaterThan(time(), $token->getExpires());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideA_token_that_has_not_expired_is_used_as_isCases(): iterable
    {
        $expiry = new DateTimeImmutable('+1 hour');

        yield 'unquoted with nanoseconds, as Go writes it' => ['    expiry: '.$expiry->format('Y-m-d\TH:i:s').'.123456789'.$expiry->format('P')];

        yield 'unquoted without a fraction' => ['    expiry: '.$expiry->format(DATE_RFC3339)];

        yield 'quoted' => ['    expiry: "'.$expiry->format(DATE_RFC3339_EXTENDED).'"'];
    }

    /**
     * Go's zero time is how the CLI says the token has no expiry, and Go's oauth2 treats that as a
     * token that never expires. It must not be refreshed as if it expired in the year 1.
     */
    public function test_the_go_zero_time_means_the_token_does_not_expire(): void
    {
        $token = $this->source('    expiry: 0001-01-01T00:00:00Z')->token();

        self::assertSame('the-access-token', $token->getToken());
        self::assertNull($token->getExpires());
    }

    public function test_the_project_is_read_from_the_config(): void
    {
        self::assertSame('example', $this->source('    expiry: 0001-01-01T00:00:00Z')->getProject());
    }
}
