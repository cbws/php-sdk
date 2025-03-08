<?php

namespace Cbws\API\OAuth2\CLI;

use Cbws\API\OAuth2\Cloudbear\Cbws;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\Yaml\Yaml;

class TokenSource implements \Cbws\API\OAuth2\TokenSource
{
    /**
     * @var string
     */
    protected $filename;

    protected $config;
    /**
     * @var []string
     */
    protected $scopes;
    /**
     * @var Cbws
     */
    protected $provider;

    public function __construct(string $filename, array $scopes)
    {
        $this->filename = $filename;
        $this->config = Yaml::parseFile($filename);
        $this->scopes = $scopes;

        $this->provider = new Cbws([]);
    }

    public function token(): AccessTokenInterface
    {
        $token = $this->getConfigToken();
        if (!$token->hasExpired()) {
            return $token;
        }

        $token = $this->provider->getAccessToken('refresh_token', [
            'client_id' => '612537ce-d5ba-475f-a77e-c1f1197ad3d5',
            'refresh_token' => $token->getRefreshToken(),
        ]);
        $this->setConfigToken($token);

        file_put_contents($this->filename, Yaml::dump($this->config));

        return $token;
    }

    protected function getConfigToken(): AccessTokenInterface
    {
        $time = strtotime('2015-02-01');
        if (!$this->config['oauth_token']['expiry']) {
            $time = date_create_from_format(DATE_RFC3339_EXTENDED, $this->config['oauth_token']['expiry'])->getTimestamp();
        }

        return new AccessToken([
            'access_token' => $this->config['oauth_token']['accesstoken'],
            'refresh_token' => $this->config['oauth_token']['refreshtoken'],
            'expires' => $time,
        ]);
    }

    protected function setConfigToken(AccessTokenInterface $token)
    {
        $this->config['oauth_token']['accesstoken'] = $token->getToken();
        $this->config['oauth_token']['refreshtoken'] = $token->getRefreshToken();
        $this->config['oauth_token']['expiry'] = date(DATE_RFC3339_EXTENDED, $token->getExpires());
    }

    public static function getConfigFile(): string
    {
        if (getenv('CBWS_CONFIG_FILE')) {
            return getenv('CBWS_CONFIG_FILE');
        }

        return $_SERVER['HOME'] . '/.cbws.yaml';
    }
}
