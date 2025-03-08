<?php

namespace Cbws\Sdk\Compute\V1alpha1;

use Cbws\Grpc\Compute\V1alpha1\Machine\State;

enum MachineState: int
{
    case Unspecified = State::STATE_UNSPECIFIED;
    case Provisioning = State::STATE_PROVISIONING;
    case Staging = State::STATE_STAGING;
    case Running = State::STATE_RUNNING;
    case Stopping = State::STATE_STOPPING;
    case Suspending = State::STATE_SUSPENDING;
    case Suspended = State::STATE_SUSPENDED;
    case Repairing = State::STATE_REPAIRING;
    case Terminated = State::STATE_TERMINATED;
}
