<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth;

use Cbws\Sdk\Auth\TokenSources\CLITokenSource;
use Cbws\Sdk\Auth\TokenSources\ServiceAccountTokenSource;
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
        // First, try the environment variable.
        $credentialsFile = getenv('CBWS_CREDENTIALS');

        if (!empty($credentialsFile)) {
            return self::fromCredentialsFile($credentialsFile, $scopes);
        }

        // Second, try a well-known file.
        try {
            if (file_exists(ServiceAccountTokenSource::getFileLocation())) {
                return self::fromCredentialsFile(ServiceAccountTokenSource::getFileLocation(), $scopes);
            }
        } catch (HomeNotDefinedException) {
            // If home is not defined then just fall through to the next method
        }

        // Third, try the CBWS CLI config
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
        return new self(new ServiceAccountTokenSource($filename, $scopes));
    }
}
