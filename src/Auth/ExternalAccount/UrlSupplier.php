<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Fetches the subject token with a GET request.
 */
final class UrlSupplier implements SubjectTokenSupplier
{
    private ClientInterface $httpClient;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $url,
        public readonly array $headers = [],
        public readonly Format $format = new Format(),
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new HttpClient(['connect_timeout' => 5, 'timeout' => 30]);
    }

    public function subjectToken(SupplierOptions $options): string
    {
        try {
            $response = $this->httpClient->request('GET', $this->url, [
                'headers' => $this->headers,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new SubjectTokenException("Could not request the subject token from {$this->url}: {$e->getMessage()}", 0, $e);
        }

        // The body is not quoted: a source that answers with an error page may still have put a token in it.
        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            throw new SubjectTokenException("The subject token URL {$this->url} answered HTTP {$response->getStatusCode()}");
        }

        return $this->format->parse((string) $response->getBody());
    }
}
