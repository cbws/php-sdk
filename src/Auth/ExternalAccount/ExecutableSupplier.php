<?php

declare(strict_types=1);

namespace Cbws\Sdk\Auth\ExternalAccount;

use Cbws\Sdk\Auth\ExternalAccount\Exception\SubjectTokenException;
use Cbws\Sdk\Common\Exception\InvalidCredentialException;

/**
 * Runs a command that prints the subject token on its standard output.
 *
 * The command is split on white space and run without a shell, so it cannot hold quoting, pipes or variable expansion.
 * It sees the environment of this process, plus CBWS_EXTERNAL_ACCOUNT_AUDIENCE and CBWS_EXTERNAL_ACCOUNT_TOKEN_TYPE, and
 * CBWS_EXTERNAL_ACCOUNT_IMPERSONATED_ACCOUNT where the configuration names one. What it writes to its standard error is
 * discarded, so that no exception carries it.
 */
final class ExecutableSupplier implements SubjectTokenSupplier
{
    /**
     * Must be set to 1 before a configuration may run a command. A configuration is found through an environment
     * variable, and whoever can point that variable at a file of their own would otherwise choose a command for this
     * process to run. Setting this variable is the process owner's statement that running one is intended.
     */
    public const ALLOW_EXECUTABLES_ENV_VAR = 'CBWS_EXTERNAL_ACCOUNT_ALLOW_EXECUTABLES';

    public const DEFAULT_TIMEOUT_MILLIS = 30000;

    public const MINIMUM_TIMEOUT_MILLIS = 5000;

    public const MAXIMUM_TIMEOUT_MILLIS = 120000;

    /**
     * What is read from a source at most. A subject token is a few kilobytes, and the bound keeps a misconfigured
     * source from filling memory.
     */
    public const MAXIMUM_TOKEN_SIZE = 1048576;

    /**
     * @var non-empty-list<string>
     */
    private array $command;

    /**
     * @param string|null $impersonatedAccount the account the configuration impersonates, which the command is told
     *
     * @throws InvalidCredentialException
     */
    public function __construct(
        string $command,
        public readonly int $timeoutMillis = self::DEFAULT_TIMEOUT_MILLIS,
        public readonly Format $format = new Format(),
        public readonly ?string $impersonatedAccount = null,
    ) {
        if (getenv(self::ALLOW_EXECUTABLES_ENV_VAR) !== '1') {
            throw new InvalidCredentialException(sprintf('credential_source runs an executable, which needs %s=1 in the environment', self::ALLOW_EXECUTABLES_ENV_VAR));
        }

        $parts = preg_split('/\s+/', trim($command), -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false || $parts === []) {
            throw new InvalidCredentialException('credential_source executable has no command');
        }

        if ($timeoutMillis < self::MINIMUM_TIMEOUT_MILLIS || $timeoutMillis > self::MAXIMUM_TIMEOUT_MILLIS) {
            throw new InvalidCredentialException(sprintf('credential_source executable timeout_millis must be from %d to %d', self::MINIMUM_TIMEOUT_MILLIS, self::MAXIMUM_TIMEOUT_MILLIS));
        }

        $this->command = $parts;
    }

    public function subjectToken(SupplierOptions $options): string
    {
        $environment = getenv();
        $environment['CBWS_EXTERNAL_ACCOUNT_AUDIENCE'] = $options->audience;
        $environment['CBWS_EXTERNAL_ACCOUNT_TOKEN_TYPE'] = $options->subjectTokenType;

        if ($this->impersonatedAccount !== null) {
            $environment['CBWS_EXTERNAL_ACCOUNT_IMPERSONATED_ACCOUNT'] = $this->impersonatedAccount;
        }

        $descriptors = [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];

        $process = @proc_open($this->command, $descriptors, $pipes, null, $environment);

        if (!is_resource($process)) {
            throw new SubjectTokenException("The subject token executable {$this->command[0]} could not be started");
        }

        $stdout = $pipes[1];
        stream_set_blocking($stdout, false);
        $deadline = microtime(true) + $this->timeoutMillis / 1000;
        $output = '';

        try {
            // Read until the command closes its output, then wait for it to exit, both within the timeout.
            while (!feof($stdout)) {
                $remaining = $this->remaining($deadline, $process);
                $read = [$stdout];
                $write = null;
                $except = null;

                if (@stream_select($read, $write, $except, (int) $remaining, (int) (fmod($remaining, 1) * 1000000)) === false) {
                    throw new SubjectTokenException("Could not read the output of the subject token executable {$this->command[0]}");
                }

                $chunk = fread($stdout, 8192);

                if (is_string($chunk) && strlen($output) < self::MAXIMUM_TOKEN_SIZE) {
                    $output .= substr($chunk, 0, self::MAXIMUM_TOKEN_SIZE - strlen($output));
                }
            }

            while (true) {
                $status = proc_get_status($process);

                if (!$status['running']) {
                    break;
                }

                $this->remaining($deadline, $process);
                usleep(10000);
            }
        } finally {
            fclose($stdout);
        }

        proc_close($process);

        if ($status['exitcode'] !== 0) {
            throw new SubjectTokenException("The subject token executable {$this->command[0]} failed with exit status {$status['exitcode']}");
        }

        return $this->format->parse($output);
    }

    /**
     * The seconds left before the deadline. Past it, the command is killed.
     *
     * @param resource $process
     *
     * @throws SubjectTokenException
     */
    private function remaining(float $deadline, $process): float
    {
        $remaining = $deadline - microtime(true);

        if ($remaining > 0) {
            return $remaining;
        }

        proc_terminate($process, 9);
        proc_close($process);

        throw new SubjectTokenException(sprintf('The subject token executable %s did not finish within %d milliseconds', $this->command[0], $this->timeoutMillis));
    }
}
