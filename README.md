# PHP SDK for CBWS cloud platform

This repository contains the SDK for the CBWS cloud platform.

Currently supported services:

- CBWS Compute

[release]: https://img.shields.io/github/release/cbws/php-sdk.svg "sdk"
[downloads]: https://img.shields.io/packagist/dt/cbws/sdk.svg?style=flat-square "sdk"

## TODOS

 - check versions of libraries to support lower versions if possible
 - scopes default `[]`

![release][release]
![downloads][downloads]

This repository contains client libraries for the CBWS platform.

## Installation

The recommended way to install composer packages is:

```
composer require cbws/sdk
```

Note you will need the PHP gRPC extension and the PHP protobuf extensions.

## Usage

### Long-running operations

Many interactions with the CBWS cloud platform will be long-running operations that run on the background, these can
be polled until the operation has finished. After which the response or error will be available.

### Getting the current operation status

Operations have a `get()` method that fetches the current state of the operation.

```php
<?php
$operation = $machine->stop();

// Get the latest status of the operation
$operation = $operation->get();

var_dump($operation->getDone());
var_dump($operation->getResponse());
if ($operation->getError() !== null) {
   throw $operation->getError();
}
```

### Async/await with PHP fibers

With the built-in PHP fibers it is possible to wait until the operation has finished, either successfully or with an error:

```php
$operation = $machine->stop();

// Get the fiber that will wait until the operation has finished.
$fiber = $operation->fiber();
$fiber->start();

while (!$fiber->isTerminated()) {
    // Get intermediate operation state
    $operation = $fiber->resume();
}

// Finished operation
$operation = $fiber->getReturn();

var_dump($operation->getDone());
var_dump($operation->getResponse());
if ($operation->getError() !== null) {
   throw $operation->getError();
}

```

### Compute

```php
// Create client for project `some-project`
$compute = new \Cbws\Sdk\Compute\Client('some-project');
```

#### Listing machines

The following example shows how to list machines using the compute service.

```php
$nextPageToken = null;
do {
    $request = ListMachinesRequest::create()
        ->withPageToken($nextPageToken)
        ->withoutState() // This is optional but will be much faster and as we don't have to check the live state of every machine

    $response = $compute->listMachines($request);
    foreach ($response->getMachines() as $machine) {
        print_r($machine);
    }

    $nextPageToken = $response->getNextPageToken();
} while (!is_null($response->getNextPageToken()));
```

The SDK also provides a helper which can automatically paginate and yield the machines as they come in.

```php
// Paginate through machines, can also use listMachines
foreach ($compute->paginateMachines(ListMachinesRequest::create()->withoutState()) as $machine) {
    print_r($machine);
}
```

#### Creating a machine

The following example shows how to create a machine using the compute service.

```php
// Start the machine creation operation in the background
$operation = $compute->createMachine(
    'some-machine-identifier',

    Machine::create()
        ->withHostname('oh-no') // this is optional, will default to the machine name
        ->withType('zones/nl-ein-1/types/g2.1') // Start with G2.1 (1 core, 2 GB of RAM and 20 GB SSD) machine type in the NL-EIN-1 availability zone,

    // Ensure we can recover after an error during the request, re-calling the createMachine call with the same
    // idempotency key will return the result of the initial call. This is recommended but optional.
    CreateMachineRequest::create()->withIdempotencyKey(Uuid::fromString('e62e7adf-9763-41aa-b2c8-2291cd035739'))
);

// Poll the operation has finished, you could also call $compute->getOperation() which does not block.
foreach ($compute->pollOperation($operation->getName()) as $operation) {
    print_r($operation);
}
```

#### Stopping a machine

The following example shows how to stop a machine using the compute service. Once the operation has finished the machine
will be in the _Terminated_ state.

```php
// Start the machine stop operation in the background
$operation = $compute->stopMachine(
    'some-machine-identifier',
    // Ensure we can recover after an error during the request, re-calling the stopMachine call with the same
    // idempotency key will return the result of the initial call. This is recommended but optional.
    StopMachineRequest::create()->withIdempotencyKey(Uuid::fromString('e62e7adf-9763-41aa-b2c8-2291cd035739'))
);

// Poll the operation has finished, you could also call $compute->getOperation() which does not block.
foreach ($compute->pollOperation($operation->getName()) as $operation) {
    print_r($operation);
}
```

#### Starting a machine

The following example shows how to start a machine using the compute service. Once the operation has finished the machine
will be in the _Running_ state.

```php
// Start the machine stop operation in the background
$operation = $compute->startMachine(
    'some-machine-identifier',
    // Ensure we can recover after an error during the request, re-calling the startMachine call with the same
    // idempotency key will return the result of the initial call. This is recommended but optional.
    StartMachineRequest::create()->withIdempotencyKey(Uuid::fromString('e62e7adf-9763-41aa-b2c8-2291cd035739'))
);

// Poll the operation has finished, you could also call $compute->getOperation() which does not block.
foreach ($compute->pollOperation($operation->getName()) as $operation) {
    print_r($operation);
}
```

#### Power cycling a machine

The following example shows how to reset/power cycle a machine using the compute service. Once the operation has
finished the machine will be in the _Running_ state.

```php
// Start the machine stop operation in the background
$operation = $compute->resetMachine(
    'some-machine-identifier',
    // Ensure we can recover after an error during the request, re-calling the resetMachine call with the same
    // idempotency key will return the result of the initial call. This is recommended but optional.
    ResetMachineRequest::create()->withIdempotencyKey(Uuid::fromString('e62e7adf-9763-41aa-b2c8-2291cd035739'))
);

// Poll the operation has finished, you could also call $compute->getOperation() which does not block.
foreach ($compute->pollOperation($operation->getName()) as $operation) {
    print_r($operation);
}
```

#### Deleting a machine

The following example shows how to delete a machine using the compute service.

```php
// Start the machine stop operation in the background
$operation = $compute->deleteMachine(
    'some-machine-identifier',
    // Ensure we can recover after an error during the request, re-calling the deleteMachine call with the same
    // idempotency key will return the result of the initial call. This is recommended but optional.
    DeleteMachineRequest::create()->withIdempotencyKey(Uuid::fromString('e62e7adf-9763-41aa-b2c8-2291cd035739'))
);

// Poll the operation has finished, you could also call $compute->getOperation() which does not block.
foreach ($compute->pollOperation($operation->getName()) as $operation) {
    print_r($operation);
}
```
