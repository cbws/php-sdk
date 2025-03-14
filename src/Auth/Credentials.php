<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth;

use Cbws\Sdk\Auth\TokenSources\CLITokenSource;
use Cbws\Sdk\Auth\TokenSources\ServiceAccountTokenSource;
use Cbws\Sdk\Auth\TokenSources\TokenSourceContract;
use Cbws\Sdk\Common\Exception\FileNotFoundException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use JsonException;

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
     * @throws FileNotFoundException|InvalidCredentialException|JsonException
     */
    public static function findDefault(array $scopes = []): ?self
    {
        // First, try the environment variable.
        if (!empty(getenv('CBWS_CREDENTIALS'))) {
            return self::fromCredentialsFile(getenv('CBWS_CREDENTIALS'), $scopes);
        }

        // Second, try a well-known file.
        if (file_exists(ServiceAccountTokenSource::getFileLocation())) {
            return self::fromCredentialsFile(ServiceAccountTokenSource::getFileLocation(), $scopes);
        }

        // Third, try the CBWS CLI config
        if (file_exists(CLITokenSource::getFileLocation())) {
            return self::fromCliConfigFile(CLITokenSource::getFileLocation(), $scopes);
        }

        return null;
    }

    /**
     * @param non-empty-string $filename
     * @param string[]         $scopes
     */
    public static function fromCliConfigFile(string $filename, array $scopes = []): self
    {
        return new self(new CLITokenSource($filename, $scopes));
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
