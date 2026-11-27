<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

/**
 * What the subject token is exchanged for, so that one supplier can serve several configurations.
 */
final class SupplierOptions
{
    public function __construct(
        public readonly string $audience,
        public readonly string $subjectTokenType,
    ) {}
}
