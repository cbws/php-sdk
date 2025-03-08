<?php

namespace Cbws\API\OAuth2;

use Exception;

class Credentials
{
    /**
     * @var TokenSource
     */
    protected $tokenSource;
    /**
     * @var string
     */
    protected $json;

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

    public static function FindDefault(array $scopes): ?Credentials
    {
        // First, try the environment variable.
        if (!empty(getenv("CLOUDBEAR_CREDENTIALS"))) {
            return self::fromCredentialsFile(getenv("CLOUDBEAR_CREDENTIALS"), $scopes);
        }

        // Second, try a well-known file.
        $filename = self::wellKnownFile();
        if (file_exists($filename)) {
            return self::fromCredentialsFile($filename, $scopes);
        }

        // Third, try the CBWS CLI config
        $cliFile = \Cbws\API\OAuth2\CLI\TokenSource::getConfigFile();
        if (file_exists($cliFile)) {
            return new Credentials(new RememberAccessToken(new \Cbws\API\OAuth2\CLI\TokenSource($cliFile, $scopes)));
        }

        return null;
    }

    public static function fromCredentialsFile(string $filename, array $scopes): Credentials
    {
        return self::fromJSON(file_get_contents($filename), $scopes);
    }

    public static function fromJSON(string $json, array $scopes): Credentials
    {
        $data = json_decode($json);
        if ($data->type !== 'service_account') {
            throw new Exception('Unknown credential type ' . $data->type);
        }

        return new Credentials(new \Cbws\API\OAuth2\ServiceAccount\TokenSource($data->client_id, $data->private_key, $data->private_key_id, $scopes));
    }

    public static function wellKnownFile()
    {
        return $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'cbws' . DIRECTORY_SEPARATOR . 'cbws.json';
    }
}
