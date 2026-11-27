<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount\Exception;

use Exception;

/**
 * The subject token could not be read from its source. The message never quotes what the source returned, which is or
 * holds the token.
 */
class SubjectTokenException extends Exception {}
