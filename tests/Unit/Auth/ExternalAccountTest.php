<?php

declare(strict_types=1);

namespace Cbws\API\Tests\Unit\Auth;

use Cbws\Grpc\Iam\V1alpha1\GenerateAccessTokenRequest as GrpcGenerateAccessTokenRequest;
use Cbws\Grpc\Iam\V1alpha1\GenerateAccessTokenResponse as GrpcGenerateAccessTokenResponse;
use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\ExternalAccount\Config;
use Cbws\Sdk\Auth\ExternalAccount\Exception\StsException;
use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;
use Cbws\Sdk\Auth\ExternalAccount\ExecutableSupplier;
use Cbws\Sdk\Auth\ExternalAccount\FileSupplier;
use Cbws\Sdk\Auth\ExternalAccount\SubjectTokenSupplier;
use Cbws\Sdk\Auth\ExternalAccount\SupplierOptions;
use Cbws\Sdk\Auth\TokenSources\ExternalAccountTokenSource;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use Cbws\Sdk\Common\Exception\PermissionDeniedException;
use Cbws\Sdk\Iam\Client as IamClient;
use Google\Protobuf\Timestamp;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * An IAM client whose gRPC-Web transport sends its requests to a Guzzle mock handler.
 */
class MockIamClient extends IamClient
{
    public function __construct(string $project, Credentials $credentials, protected HttpClient $httpClient)
    {
        parent::__construct($project, $credentials, null, self::TRANSPORT_GRPC_WEB);
    }

    public function getTransportOptions(): array
    {
        return parent::getTransportOptions() + ['http_client' => $this->httpClient];
    }
}

class ExternalAccountTest extends TestCase
{
    private const AUDIENCE = 'projects/pool-project/workloadIdentityPools/ci/providers/gitlab';

    private const SERVICE_ACCOUNT = 'projects/app/serviceAccounts/deploy@app.iam-sa.cbws.xyz';

    private const TOKEN_URL = 'https://sts.example/v1alpha1/token';

    /**
     * @var list<RequestInterface>
     */
    private array $stsRequests = [];

    /**
     * @var list<RequestInterface>
     */
    private array $iamRequests = [];

    /**
     * @var array<string, false|string>
     */
    private array $environment = [];

    protected function setUp(): void
    {
        foreach (['CBWS_ACCESS_TOKEN', 'CBWS_CREDENTIALS', ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR] as $name) {
            $this->environment[$name] = getenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : "{$name}={$value}");
        }
    }

    public function test_file_source_exchanges_with_sts(): void
    {
        $source = $this->source($this->config($this->tokenFile("subject-1\n")), [$this->stsToken('sts-1')]);

        $token = $source->token();

        self::assertSame('sts-1', $token->getToken());
        self::assertEqualsWithDelta(time() + 600, $token->getExpires(), 5);

        self::assertCount(1, $this->stsRequests);
        $request = $this->stsRequests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame(self::TOKEN_URL, (string) $request->getUri());
        self::assertSame([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
            'subject_token' => 'subject-1',
            'subject_token_type' => Config::TOKEN_TYPE_JWT,
            'requested_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
            'audience' => self::AUDIENCE,
        ], $this->form($request));
    }

    public function test_token_is_reused_until_close_to_expiry(): void
    {
        $source = $this->source($this->config($this->tokenFile('subject')), [$this->stsToken('sts-1')]);

        $source->token();
        $source->token();

        self::assertCount(1, $this->stsRequests);
    }

    /**
     * A token within REFRESH_MARGIN of its expiry is replaced, and the replacement is exchanged with whatever the
     * subject token is by then.
     */
    public function test_token_is_replaced_before_expiry_with_the_current_subject_token(): void
    {
        $path = $this->tokenFile('subject-1');
        $source = $this->source($this->config($path), [
            $this->stsToken('sts-1', ExternalAccountTokenSource::REFRESH_MARGIN - 10),
            $this->stsToken('sts-2'),
        ]);

        self::assertSame('sts-1', $source->token()->getToken());

        file_put_contents($path, 'subject-2');

        self::assertSame('sts-2', $source->token()->getToken());
        self::assertCount(2, $this->stsRequests);
        self::assertSame('subject-2', $this->form($this->stsRequests[1])['subject_token']);
    }

    public function test_file_source_json_format(): void
    {
        $config = Config::fromJson($this->json(['credential_source' => [
            'file' => $this->tokenFile('{"id_token": "subject-json", "other": 1}'),
            'format' => ['type' => 'json', 'subject_token_field_name' => 'id_token'],
        ]]));

        $this->source($config, [$this->stsToken('sts-1')])->token();

        self::assertSame('subject-json', $this->form($this->stsRequests[0])['subject_token']);
    }

    /**
     * @param array<string, mixed>|null $format
     */
    #[DataProvider('provideFormat_refuses_output_without_a_tokenCases')]
    public function test_format_refuses_output_without_a_token(?array $format, string $raw): void
    {
        $source = ['file' => $this->tokenFile($raw)];

        if ($format !== null) {
            $source['format'] = $format;
        }

        try {
            $this->source(Config::fromJson($this->json(['credential_source' => $source])), [])->token();
            self::fail('The token was read');
        } catch (SubjectTokenException $e) {
            self::assertStringNotContainsString('secret', $e->getMessage());
        }

        self::assertCount(0, $this->stsRequests);
    }

    /**
     * @return array<string, array{array<string, mixed>|null, string}>
     */
    public static function provideFormat_refuses_output_without_a_tokenCases(): iterable
    {
        $json = ['type' => 'json', 'subject_token_field_name' => 'token'];

        return [
            'empty text' => [null, " \n"],
            'not JSON' => [$json, 'secret-not-json'],
            'missing field' => [$json, '{"other": "secret-other"}'],
            'field not a string' => [$json, '{"token": 1}'],
            'empty field' => [$json, '{"token": ""}'],
        ];
    }

    public function test_url_source(): void
    {
        $urlRequests = [];
        $config = Config::fromJson($this->json(['credential_source' => [
            'url' => 'https://tokens.example/token',
            'headers' => ['Metadata-Flavor' => 'CBWS'],
            'format' => ['type' => 'json', 'subject_token_field_name' => 'value'],
        ]]), $this->httpClient([new Response(200, [], '{"value": "subject-url"}')], $urlRequests));

        $this->source($config, [$this->stsToken('sts-1')])->token();

        self::assertCount(1, $urlRequests);
        self::assertSame('GET', $urlRequests[0]->getMethod());
        self::assertSame('https://tokens.example/token', (string) $urlRequests[0]->getUri());
        self::assertSame('CBWS', $urlRequests[0]->getHeaderLine('Metadata-Flavor'));
        self::assertSame('subject-url', $this->form($this->stsRequests[0])['subject_token']);
    }

    /**
     * A token URL that answers with an error may have put a token in its body, so only the status is reported.
     */
    public function test_url_source_failure_quotes_no_body(): void
    {
        $urlRequests = [];
        $config = Config::fromJson($this->json(['credential_source' => ['url' => 'https://tokens.example/token']]), $this->httpClient([new Response(403, [], 'secret-in-body')], $urlRequests));

        try {
            $this->source($config, [])->token();
            self::fail('The token was read');
        } catch (SubjectTokenException $e) {
            self::assertStringNotContainsString('secret-in-body', $e->getMessage());
            self::assertStringContainsString('403', $e->getMessage());
        }

        self::assertCount(0, $this->stsRequests);
    }

    public function test_executable_source(): void
    {
        putenv(ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR.'=1');

        // The script answers with what it was told, so the test sees both that it ran and what environment it ran in.
        $script = $this->script('echo "$1:$CBWS_EXTERNAL_ACCOUNT_AUDIENCE:$CBWS_EXTERNAL_ACCOUNT_TOKEN_TYPE:$CBWS_EXTERNAL_ACCOUNT_IMPERSONATED_ACCOUNT"');
        $config = Config::fromJson($this->json(['credential_source' => ['executable' => ['command' => "{$script} subject-exec"]]]));

        $this->source($config, [$this->stsToken('sts-1')])->token();

        self::assertSame('subject-exec:'.self::AUDIENCE.':'.Config::TOKEN_TYPE_JWT.':', $this->form($this->stsRequests[0])['subject_token']);
    }

    public function test_executable_source_names_the_impersonated_account(): void
    {
        putenv(ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR.'=1');

        $script = $this->script('echo "$CBWS_EXTERNAL_ACCOUNT_IMPERSONATED_ACCOUNT"');
        $config = Config::fromJson($this->json([
            'service_account_impersonation' => self::SERVICE_ACCOUNT,
            'credential_source' => ['executable' => ['command' => $script]],
        ]));

        $this->source($config, [$this->stsToken('sts-1')], [$this->iamToken('sa-1')])->token();

        self::assertSame(self::SERVICE_ACCOUNT, $this->form($this->stsRequests[0])['subject_token']);
    }

    public function test_executable_source_needs_to_be_allowed(): void
    {
        putenv(ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR);

        $this->expectException(InvalidCredentialException::class);
        $this->expectExceptionMessage(ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR);

        Config::fromJson($this->json(['credential_source' => ['executable' => ['command' => '/bin/true']]]));
    }

    /**
     * Neither output stream reaches the exception, since either may hold a token.
     */
    public function test_executable_failure_quotes_no_output(): void
    {
        putenv(ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR.'=1');

        $script = $this->script("echo secret-stdout\necho secret-stderr >&2\nexit 3");
        $config = Config::fromJson($this->json(['credential_source' => ['executable' => ['command' => $script]]]));

        try {
            $this->source($config, [])->token();
            self::fail('The token was read');
        } catch (SubjectTokenException $e) {
            self::assertStringNotContainsString('secret', $e->getMessage());
            self::assertStringContainsString('exit status 3', $e->getMessage());
        }
    }

    public function test_executable_timeout_bounds(): void
    {
        putenv(ExecutableSupplier::ALLOW_EXECUTABLES_ENV_VAR.'=1');

        foreach ([4999, 120001] as $millis) {
            try {
                new ExecutableSupplier('/bin/true', $millis);
                self::fail("timeout_millis {$millis} was accepted");
            } catch (InvalidCredentialException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function test_subject_token_supplier_in_code(): void
    {
        $supplier = new class implements SubjectTokenSupplier {
            public ?SupplierOptions $options = null;

            public function subjectToken(SupplierOptions $options): string
            {
                $this->options = $options;

                return 'subject-supplied';
            }
        };

        $this->source(new Config(self::AUDIENCE, Config::TOKEN_TYPE_JWT, $supplier, self::TOKEN_URL), [$this->stsToken('sts-1')])->token();

        self::assertSame(self::AUDIENCE, $supplier->options?->audience);
        self::assertSame(Config::TOKEN_TYPE_JWT, $supplier->options->subjectTokenType);
        self::assertSame('subject-supplied', $this->form($this->stsRequests[0])['subject_token']);
    }

    /**
     * The OAuth 2.0 error code reaches the caller, and the subject token does not reach the message.
     */
    #[DataProvider('provideSts_errorsCases')]
    public function test_sts_errors(string $code): void
    {
        $refusal = new Response(400, ['content-type' => 'application/json'], (string) json_encode(['error' => $code, 'error_description' => 'refused']));
        $source = $this->source($this->config($this->tokenFile('secret-subject')), [$refusal]);

        try {
            $source->token();
            self::fail('The exchange succeeded');
        } catch (StsException $e) {
            self::assertSame($code, $e->getError());
            self::assertSame('refused', $e->getErrorDescription());
            self::assertSame(400, $e->getStatusCode());
            self::assertStringNotContainsString('secret-subject', $e->getMessage());
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideSts_errorsCases(): iterable
    {
        return [
            StsException::ERROR_INVALID_GRANT => [StsException::ERROR_INVALID_GRANT],
            StsException::ERROR_UNAUTHORIZED_CLIENT => [StsException::ERROR_UNAUTHORIZED_CLIENT],
            StsException::ERROR_INVALID_REQUEST => [StsException::ERROR_INVALID_REQUEST],
            StsException::ERROR_UNSUPPORTED_GRANT_TYPE => [StsException::ERROR_UNSUPPORTED_GRANT_TYPE],
            StsException::ERROR_SERVER_ERROR => [StsException::ERROR_SERVER_ERROR],
        ];
    }

    public function test_sts_error_without_an_oauth_body(): void
    {
        $source = $this->source($this->config($this->tokenFile('subject')), [new Response(502, [], 'upstream gone')]);

        try {
            $source->token();
            self::fail('The exchange succeeded');
        } catch (StsException $e) {
            self::assertNull($e->getError());
            self::assertSame(502, $e->getStatusCode());
        }
    }

    public function test_sts_response_without_expiry_is_refused(): void
    {
        $source = $this->source($this->config($this->tokenFile('subject')), [new Response(200, [], '{"access_token": "sts-1"}')]);

        $this->expectException(StsException::class);

        $source->token();
    }

    /**
     * The STS token is presented to IAM, and the service account's token is what the caller gets.
     */
    public function test_impersonation(): void
    {
        $config = $this->config($this->tokenFile('subject'), self::SERVICE_ACCOUNT);
        $source = $this->source($config, [$this->stsToken('sts-1')], [$this->iamToken('sa-1')], ['cbws.compute', 'cbws.iam']);

        $token = $source->token();

        self::assertSame('sa-1', $token->getToken());
        self::assertSame(1795438800, $token->getExpires());
        self::assertSame('app', $source->getProject());

        self::assertCount(1, $this->iamRequests);
        $request = $this->iamRequests[0];
        self::assertSame('https://iam.cbws.cloud/cbws.iam.v1alpha1.IAMService/GenerateAccessToken', (string) $request->getUri());
        self::assertSame('Bearer sts-1', $request->getHeaderLine('authorization'));

        // The body is one gRPC-Web frame: a flag byte, a four-byte length and the message.
        $sent = new GrpcGenerateAccessTokenRequest();
        $sent->mergeFromString(substr((string) $request->getBody(), 5));
        self::assertSame(self::SERVICE_ACCOUNT, $sent->getName());
        self::assertSame(['cbws.compute', 'cbws.iam'], iterator_to_array($sent->getScope()));
    }

    public function test_impersonation_refused(): void
    {
        $config = $this->config($this->tokenFile('subject'), self::SERVICE_ACCOUNT);
        $source = $this->source($config, [$this->stsToken('sts-1')], [$this->grpcWeb(null, "grpc-status:7\r\ngrpc-message:denied\r\n")]);

        try {
            $source->token();
            self::fail('IAM minted a token');
        } catch (PermissionDeniedException $e) {
            self::assertStringNotContainsString('sts-1', $e->getMessage());
        }
    }

    public function test_impersonation_without_expiry_is_refused(): void
    {
        $config = $this->config($this->tokenFile('subject'), self::SERVICE_ACCOUNT);
        $source = $this->source($config, [$this->stsToken('sts-1')], [$this->grpcWeb(new GrpcGenerateAccessTokenResponse(['access_token' => 'sa-forever']))]);

        $this->expectException(UnexpectedValueException::class);

        $source->token();
    }

    /**
     * An STS refusal ends the fetch before IAM is asked.
     */
    public function test_impersonation_needs_the_exchange(): void
    {
        $config = $this->config($this->tokenFile('subject'), self::SERVICE_ACCOUNT);
        $refusal = new Response(403, [], '{"error": "unauthorized_client"}');
        $source = $this->source($config, [$refusal], [$this->iamToken('sa-1')]);

        try {
            $source->token();
            self::fail('The exchange succeeded');
        } catch (StsException $e) {
            self::assertSame(StsException::ERROR_UNAUTHORIZED_CLIENT, $e->getError());
        }

        self::assertCount(0, $this->iamRequests);
    }

    /**
     * @param array<string, mixed> $override
     */
    #[DataProvider('provideInvalid_configurationCases')]
    public function test_invalid_configuration(array $override): void
    {
        $this->expectException(InvalidCredentialException::class);

        Config::fromJson($this->json($override));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function provideInvalid_configurationCases(): iterable
    {
        return [
            'wrong type' => [['type' => 'service_account']],
            'audience not a provider' => [['audience' => 'projects/p/workloadIdentityPools/ci']],
            'unsupported token type' => [['subject_token_type' => 'urn:ietf:params:oauth:token-type:saml2']],
            'token URL not HTTP' => [['token_url' => 'file:///etc/passwd']],
            'account not a name' => [['service_account_impersonation' => 'deploy@app.iam-sa.cbws.xyz']],
            'account with a query' => [['service_account_impersonation' => 'projects/p/serviceAccounts/a?b']],
            'no source' => [['credential_source' => null]],
            'two sources' => [['credential_source' => ['file' => '/token', 'url' => 'https://tokens.example']]],
            'empty source' => [['credential_source' => []]],
            'unknown format' => [['credential_source' => ['file' => '/token', 'format' => ['type' => 'xml']]]],
            'json format with no name' => [['credential_source' => ['file' => '/token', 'format' => ['type' => 'json']]]],
        ];
    }

    public function test_project(): void
    {
        $supplier = new FileSupplier('/token');

        self::assertSame('pool-project', (new Config(self::AUDIENCE, Config::TOKEN_TYPE_JWT, $supplier))->getProject());
        self::assertSame('pool-project', (new Config(self::AUDIENCE, Config::TOKEN_TYPE_JWT, $supplier, serviceAccountImpersonation: 'projects/-/serviceAccounts/a@b'))->getProject());
        self::assertSame('app', (new Config(self::AUDIENCE, Config::TOKEN_TYPE_JWT, $supplier, serviceAccountImpersonation: self::SERVICE_ACCOUNT))->getProject());
    }

    /**
     * A CI job points CBWS_CREDENTIALS at an external account configuration, which is found as a key file would be.
     */
    public function test_find_default_reads_an_external_account(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cbws-external-account-');
        self::assertIsString($path);
        file_put_contents($path, $this->json(['credential_source' => ['file' => $this->tokenFile('subject')]]));
        putenv('CBWS_ACCESS_TOKEN');
        putenv("CBWS_CREDENTIALS={$path}");

        $credentials = Credentials::findDefault(['cbws.iam']);

        self::assertInstanceOf(Credentials::class, $credentials);
        self::assertInstanceOf(ExternalAccountTokenSource::class, $credentials->getTokenSource());
        self::assertSame('pool-project', $credentials->getTokenSource()->getProject());
    }

    public function test_from_credential_refuses_an_unknown_type(): void
    {
        $this->expectException(InvalidCredentialException::class);

        Credentials::fromCredential('{"type": "authorized_user"}');
    }

    /**
     * @param list<ResponseInterface> $stsResponses
     * @param list<ResponseInterface> $iamResponses
     * @param string[]                $scopes
     */
    private function source(Config $config, array $stsResponses, array $iamResponses = [], array $scopes = []): ExternalAccountTokenSource
    {
        $iamHttpClient = $this->httpClient($iamResponses, $this->iamRequests);

        return new ExternalAccountTokenSource(
            $config,
            $scopes,
            $this->httpClient($stsResponses, $this->stsRequests),
            static fn (string $project, Credentials $credentials): IamClient => new MockIamClient($project, $credentials, $iamHttpClient),
        );
    }

    private function config(string $tokenPath, ?string $serviceAccount = null): Config
    {
        return new Config(self::AUDIENCE, Config::TOKEN_TYPE_JWT, new FileSupplier($tokenPath), self::TOKEN_URL, $serviceAccount);
    }

    /**
     * An external account configuration as a workload writes it, with the given fields replaced.
     *
     * @param array<string, mixed> $override
     */
    private function json(array $override): string
    {
        return (string) json_encode($override + [
            'type' => 'external_account',
            'audience' => self::AUDIENCE,
            'subject_token_type' => Config::TOKEN_TYPE_JWT,
            'token_url' => self::TOKEN_URL,
            'credential_source' => ['file' => '/token'],
        ]);
    }

    /**
     * @param list<ResponseInterface> $responses
     * @param list<RequestInterface>  $requests
     */
    private function httpClient(array $responses, array &$requests): HttpClient
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::tap(static function (RequestInterface $request) use (&$requests): void {
            $requests[] = $request;
        }));

        return new HttpClient(['handler' => $handler]);
    }

    private function stsToken(string $token, int $expiresIn = 600): Response
    {
        return new Response(200, ['content-type' => 'application/json'], (string) json_encode([
            'access_token' => $token,
            'issued_token_type' => 'urn:ietf:params:oauth:token-type:access_token',
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
        ]));
    }

    private function iamToken(string $token): Response
    {
        return $this->grpcWeb(new GrpcGenerateAccessTokenResponse([
            'access_token' => $token,
            'expire_time' => new Timestamp(['seconds' => 1795438800]),
        ]));
    }

    private function grpcWeb(?GrpcGenerateAccessTokenResponse $response, string $trailers = "grpc-status:0\r\ngrpc-message:\r\n"): Response
    {
        $body = '';

        if ($response !== null) {
            $message = $response->serializeToString();
            $body = pack('CN', 0, strlen($message)).$message;
        }

        return new Response(200, ['content-type' => 'application/grpc-web+proto'], $body.pack('CN', 0x80, strlen($trailers)).$trailers);
    }

    /**
     * @return array<string, string>
     */
    private function form(RequestInterface $request): array
    {
        parse_str((string) $request->getBody(), $form);

        /** @var array<string, string> $form */
        return $form;
    }

    private function tokenFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbws-subject-');
        self::assertIsString($path);
        file_put_contents($path, $content);

        return $path;
    }

    private function script(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cbws-token-');
        self::assertIsString($path);
        file_put_contents($path, "#!/bin/sh\n{$body}\n");
        chmod($path, 0o700);

        return $path;
    }
}
