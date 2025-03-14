<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\Oauth2;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Client\Grant\ClientCredentials;
use RangeException;

class JWTClientCredentials extends ClientCredentials
{
    /**
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $options
     *
     * @return array{'client_assertion_type': string}
     */
    public function prepareRequestParameters(array $defaults, array $options): array
    {
        $defaults['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

        return parent::prepareRequestParameters($defaults, $options);
    }

    /**
     * @param non-empty-string $clientID
     */
    public static function generateAssertion(string $clientID, Key $key, string $privateKeyID): string
    {
        $configuration = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);
        $token = $configuration->builder()
            ->permittedFor('https://accounts.cbws.cloud/oauth2/token') // Configures the audience (aud claim)
            ->issuedBy($clientID) // Configures the issuer (iss claim)
            ->identifiedBy(self::random_str(16)) // Configures the id (jti claim), replicating as a header item
            ->relatedTo($clientID)
            ->withHeader('kid', $privateKeyID)
            ->issuedAt(new DateTimeImmutable()) // Configures the time that the token was issue (iat claim)
            ->expiresAt(new DateTimeImmutable('+1 hour')) // Configures the expiration time of the token (exp claim)
            ->getToken(new Sha256(), $key); // Retrieves the generated token

        return $token->toString();
    }

    /**
     * @return non-empty-string
     */
    protected static function random_str(int $length = 64): string
    {
        if ($length < 1) {
            throw new RangeException('Length must be a positive integer');
        }

        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;

        for ($i = 0; $i < $length; $i++) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }

        return implode('', $pieces);
    }
}
