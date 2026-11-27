<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;

/**
 * Returns the current subject token of the workload. It is called for every exchange, so it returns a token that is
 * valid now rather than one it kept from an earlier call.
 */
interface SubjectTokenSupplier
{
    /**
     * @return non-empty-string
     *
     * @throws SubjectTokenException
     */
    public function subjectToken(SupplierOptions $options): string;
}
