# PHP libraries for CBWS

## TODOS

 - check versions of libraries to support lower versions if possible
 - scopes default `[]`

![release][release]
![downloads][downloads]

This repository contains client libraries for the CBWS platform.

## Installation

The recommended way to install composer packages is:

```
composer require cbws/php-cbws
```

[release]: https://img.shields.io/github/release/cbws/php-cbws.svg "php-cbws"
[downloads]: https://img.shields.io/packagist/dt/cbws/php-cbws.svg?style=flat-square "php-cbws"

## Usage

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
