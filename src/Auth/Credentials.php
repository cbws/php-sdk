<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth;

use Cbws\Sdk\Auth\ExternalAccount\Config;
use Cbws\Sdk\Auth\TokenSources\CLITokenSource;
use Cbws\Sdk\Auth\TokenSources\ExternalAccountTokenSource;
use Cbws\Sdk\Auth\TokenSources\ServiceAccountTokenSource;
use Cbws\Sdk\Auth\TokenSources\StaticTokenSource;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Cbws\Sdk\Common\Exception\FileNotFoundException;
use Cbws\Sdk\Common\Exception\HomeNotDefinedException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use JsonException;
use Symfony\Component\Yaml\Exception\ParseException;

class Credentials
{
    protected TokenSourceContract $tokenSource;

    protected function __construct(TokenSourceContract $tokenSource)
    {
        $this->tokenSource = $tokenSource;
    }

    public function getTokenSource(): TokenSourceContract
    {
        return $this->tokenSource;
    }

    /**
     * @param string[] $scopes
     *
     * @throws FileNotFoundException|InvalidCredentialException|JsonException|ParseException
     */
    public static function findDefault(array $scopes = []): ?self
    {
        // First, try a bare access token, the variable the CBWS CLI reads too. It was minted by whoever set it, so
        // the scopes are not applied to it.
        $accessToken = getenv('CBWS_ACCESS_TOKEN');

        if (is_string($accessToken) && $accessToken !== '') {
            return self::fromAccessToken($accessToken);
        }

        // Second, try the environment variable.
        $credentialsFile = getenv('CBWS_CREDENTIALS');

        if (!empty($credentialsFile)) {
            return self::fromCredentialsFile($credentialsFile, $scopes);
        }

        // Third, try a well-known file.
        try {
            if (file_exists(ServiceAccountTokenSource::getFileLocation())) {
                return self::fromCredentialsFile(ServiceAccountTokenSource::getFileLocation(), $scopes);
            }
        } catch (HomeNotDefinedException) {
            // If home is not defined then just fall through to the next method
        }

        // Fourth, try the CBWS CLI config
        try {
            if (file_exists(CLITokenSource::getFileLocation())) {
                return self::fromCliConfigFile(CLITokenSource::getFileLocation(), $scopes);
            }
        } catch (HomeNotDefinedException) {
            // If home is not defined then just fall through to the next method
        }

        return null;
    }

    /**
     * Credentials from an access token obtained elsewhere, such as from Vault. The token is not refreshed, so the
     * caller supplies a new one before it expires. A bare token names no project, so give the project here or to the
     * client.
     *
     * @param non-empty-string $accessToken
     */
    public static function fromAccessToken(string $accessToken, ?string $project = null): self
    {
        return new self(new StaticTokenSource($accessToken, $project));
    }

    /**
     * @param non-empty-string $filename
     * @param string[]         $scopes
     *
     * @throws ParseException
     */
    public static function fromCliConfigFile(string $filename, array $scopes = []): self
    {
        return new self(new CLITokenSource($filename, $scopes));
    }

    /**
     * @param non-empty-string $json
     * @param string[]         $scopes
     *
     * @throws InvalidCredentialException|JsonException
     */
    public static function fromCredential(string $json, array $scopes = []): self
    {
        // The type field selects the kind of credentials. A service account key is "service_account", and is also
        // what a file without a type is read as.
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $type = is_array($data) ? ($data['type'] ?? null) : null;

        if ($type === Config::TYPE) {
            return self::fromExternalAccount(Config::fromJson($json), $scopes);
        }

        if ($type !== null && $type !== 'service_account') {
            throw new InvalidCredentialException(sprintf('Unknown credential type %s, expected service_account or %s', json_encode($type), Config::TYPE));
        }

        return new self(ServiceAccountTokenSource::fromJson($json, $scopes));
    }

    /**
     * @param non-empty-string $filename
     * @param string[]         $scopes
     *
     * @throws FileNotFoundException|InvalidCredentialException|JsonException
     */
    public static function fromCredentialsFile(string $filename, array $scopes = []): self
    {
        $json = @file_get_contents($filename);

        if (!$json) {
            throw new FileNotFoundException("File not found: {$filename}");
        }

        return self::fromCredential($json, $scopes);
    }

    /**
     * Credentials for a workload outside CBWS, which exchanges a token from its own platform at STS. Build the
     * configuration in code to supply the subject token with a SubjectTokenSupplier.
     *
     * @param string[] $scopes the scopes of an impersonated account's token
     */
    public static function fromExternalAccount(Config $config, array $scopes = []): self
    {
        return new self(new ExternalAccountTokenSource($config, $scopes));
    }
}
