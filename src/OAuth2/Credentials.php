<?php

namespace Cbws\API\OAuth2;

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
        $cliFile = CLI\TokenSource::getConfigFile();
        if (file_exists($cliFile)) {
            return new Credentials(new RememberAccessToken(new CLI\TokenSource($cliFile, $scopes)));
        }
    }
}
