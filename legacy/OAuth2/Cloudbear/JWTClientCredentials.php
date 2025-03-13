<?php

declare(strict_types=1);

namespace Cbws\API\OAuth2\Cloudbear;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Client\Grant\ClientCredentials;
use function Cbws\API\OAuth2\Functional\random_str;

class JWTClientCredentials extends ClientCredentials
{
    public function prepareRequestParameters(array $defaults, array $options): array
    {
        $defaults['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

        return parent::prepareRequestParameters($defaults, $options);
    }

    public static function generateAssertion(string $clientID, Key $key, string $privateKeyID): string
    {
        $configuration = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);
        $token = $configuration->builder()
            ->permittedFor('https://accounts.cbws.cloud/oauth2/token') // Configures the audience (aud claim)
            ->issuedBy($clientID) // Configures the issuer (iss claim)
            ->identifiedBy(random_str(16)) // Configures the id (jti claim), replicating as a header item
            ->relatedTo($clientID)
            ->withHeader('kid', $privateKeyID)
            ->issuedAt(new DateTimeImmutable()) // Configures the time that the token was issue (iat claim)
            ->expiresAt(new DateTimeImmutable('+1 hour')) // Configures the expiration time of the token (exp claim)
            ->getToken(new Sha256(), $key); // Retrieves the generated token

        return $token->toString();
    }
}
