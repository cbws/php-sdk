<?php

namespace Cbws\API\OAuth2\Cloudbear;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Client\Grant\ClientCredentials;

class JWTClientCredentials extends ClientCredentials
{
    public function prepareRequestParameters(array $defaults, array $options)
    {
        $defaults['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

        return parent::prepareRequestParameters($defaults, $options);
    }

    public static function generateAssertion(string $clientID, Key $key, string $privateKeyID)
    {
        $token = (new Builder())
            ->permittedFor('https://accounts.cloudbear.nl/oauth2/token') // Configures the audience (aud claim)
            ->issuedBy($clientID) // Configures the issuer (iss claim)
            ->identifiedBy(random_str(16), true) // Configures the id (jti claim), replicating as a header item
            ->relatedTo($clientID)
            ->withHeader('kid', $privateKeyID)
            ->issuedAt(time()) // Configures the time that the token was issue (iat claim)
            ->expiresAt(time() + 3600) // Configures the expiration time of the token (exp claim)
            ->getToken(new Sha256(), $key); // Retrieves the generated token

        return (string)$token;
    }
}
