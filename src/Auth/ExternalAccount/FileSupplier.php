<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;

/**
 * Reads the subject token from a file, such as the projected service account token of a Kubernetes pod.
 */
final class FileSupplier implements SubjectTokenSupplier
{
    public function __construct(
        public readonly string $path,
        public readonly Format $format = new Format(),
    ) {}

    public function subjectToken(SupplierOptions $options): string
    {
        // A subject token is a few kilobytes at most; the bound keeps a misconfigured path from filling memory.
        $raw = @file_get_contents($this->path, false, null, 0, ExecutableSupplier::MAXIMUM_TOKEN_SIZE);

        if ($raw === false) {
            throw new SubjectTokenException("Could not read the subject token from {$this->path}");
        }

        return $this->format->parse($raw);
    }
}
