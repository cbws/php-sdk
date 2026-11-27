<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;
use JsonException;

/**
 * How the subject token is read out of what a source returns: all of it, or the string in one field of a JSON object.
 */
final class Format
{
    public const TYPE_TEXT = 'text';

    public const TYPE_JSON = 'json';

    /**
     * @param self::TYPE_* $type
     *
     * @throws InvalidCredentialException
     */
    public function __construct(
        public readonly string $type = self::TYPE_TEXT,
        public readonly ?string $subjectTokenFieldName = null,
    ) {
        if ($type === self::TYPE_JSON && ($subjectTokenFieldName === null || $subjectTokenFieldName === '')) {
            throw new InvalidCredentialException('credential_source format json needs a subject_token_field_name');
        }
    }

    /**
     * The format of a credential_source, from its format object. Without one, the whole response is the token.
     *
     * @throws InvalidCredentialException
     */
    public static function fromConfig(mixed $format): self
    {
        if ($format === null) {
            return new self();
        }

        if (!is_array($format)) {
            throw new InvalidCredentialException('credential_source format is not an object');
        }

        $type = $format['type'] ?? self::TYPE_TEXT;
        $field = $format['subject_token_field_name'] ?? null;

        if ($type === '') {
            $type = self::TYPE_TEXT;
        }

        if ($type !== self::TYPE_TEXT && $type !== self::TYPE_JSON) {
            throw new InvalidCredentialException(sprintf('credential_source format type %s is not supported, expected text or json', json_encode($type)));
        }

        if ($field !== null && !is_string($field)) {
            throw new InvalidCredentialException('credential_source format subject_token_field_name is not a string');
        }

        return new self($type, $field);
    }

    /**
     * Read the subject token out of what a source returned. No exception quotes $raw, which is or holds the token.
     *
     * @return non-empty-string
     *
     * @throws SubjectTokenException
     */
    public function parse(string $raw): string
    {
        if ($this->type === self::TYPE_TEXT) {
            $token = trim($raw);
        } else {
            try {
                $fields = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new SubjectTokenException('The subject token source did not return a JSON object');
            }

            $value = is_array($fields) ? ($fields[(string) $this->subjectTokenFieldName] ?? null) : null;

            if (!is_string($value)) {
                throw new SubjectTokenException(sprintf('The subject token source returned no string in %s', json_encode($this->subjectTokenFieldName)));
            }

            $token = trim($value);
        }

        if ($token === '') {
            throw new SubjectTokenException('The subject token source returned an empty token');
        }

        return $token;
    }
}
