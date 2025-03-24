<?php

declare(strict_types=1);

namespace Cbws\Sdk\Compute\Enums;

enum MachineState: int
{
    case Unspecified = 0;

    case Provisioning = 1;

    case Staging = 2;

    case Running = 3;

    case Stopping = 4;

    case Suspending = 5;

    case Suspended = 6;

    case Repairing = 7;

    case Terminated = 8;
}
