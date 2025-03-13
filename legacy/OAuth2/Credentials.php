<?php

declare(strict_types=1);

namespace Cbws\API\OAuth2;

use Exception;
use JsonException;

use const DIRECTORY_SEPARATOR;

class Credentials
{
    protected TokenSource $tokenSource;

    protected string $json;

    public function __construct(TokenSource $tokenSource)
    {
        $this->tokenSource = $tokenSource;
    }

    public function getTokenSource(): TokenSource
    {
        return $this->tokenSource;
    }

    public function getJSON(): string
    {
        return $this->json;
    }

    /**
     * @param string[] $scopes
     */
    public static function findDefault(array $scopes): ?self
    {
        // First, try the environment variable.
        if (!empty(getenv('CBWS_CREDENTIALS'))) {
            return self::fromCredentialsFile(getenv('CBWS_CREDENTIALS'), $scopes);
        }

        // Second, try a well-known file.
        $filename = self::wellKnownFile();

        if (file_exists($filename)) {
            return self::fromCredentialsFile($filename, $scopes);
        }

        // Third, try the CBWS CLI config
        $cliFile = \Cbws\API\OAuth2\CLI\TokenSource::getConfigFile();

        if (file_exists($cliFile)) {
            return new self(new RememberAccessToken(new \Cbws\API\OAuth2\CLI\TokenSource($cliFile, $scopes)));
        }

        return null;
    }

    /**
     * @param string[] $scopes
     * @throws JsonException
     */
    public static function fromCredentialsFile(string $filename, array $scopes): self
    {
        return self::fromJSON(file_get_contents($filename) ?: '', $scopes);
    }

    /**
     * @param string[] $scopes
     *
     * @throws JsonException
     * @throws Exception
     */
    public static function fromJSON(string $json, array $scopes): self
    {
        // TODO make class
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if ($data->type !== 'service_account') {
            throw new Exception('Unknown credential type '.$data->type);
        }

        return new self(new \Cbws\API\OAuth2\ServiceAccount\TokenSource($data->client_id, $data->private_key, $data->private_key_id, $scopes));
    }

    public static function wellKnownFile(): string
    {
        return sprintf("%s/.config/cbws/cbws.json", $_SERVER['HOME']);
    }
}
