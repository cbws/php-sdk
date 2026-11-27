<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount\Exception;

use Exception;
use Throwable;

/**
 * STS refused to exchange the subject token, or could not be reached. getError() is the OAuth 2.0 error code STS
 * answered with, one of the ERROR_* constants, and null where there was no answer to read one from.
 */
class StsException extends Exception
{
    /**
     * A subject token STS did not accept: one that is malformed, expired, or not issued for the provider in the
     * audience.
     */
    public const ERROR_INVALID_GRANT = 'invalid_grant';

    /**
     * A subject token the attribute condition of the provider refused.
     */
    public const ERROR_UNAUTHORIZED_CLIENT = 'unauthorized_client';

    /**
     * An exchange request STS could not read.
     */
    public const ERROR_INVALID_REQUEST = 'invalid_request';

    /**
     * A grant type STS does not serve.
     */
    public const ERROR_UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';

    /**
     * A failure inside STS.
     */
    public const ERROR_SERVER_ERROR = 'server_error';

    public function __construct(
        string $message,
        protected ?string $error = null,
        protected ?string $errorDescription = null,
        protected ?int $statusCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    /**
     * The OAuth 2.0 error code STS answered with.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    /**
     * The HTTP status STS answered with.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
