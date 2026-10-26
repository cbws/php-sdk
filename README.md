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

The SDK does not need a PHP extension. The `grpc` extension is optional; see [Transports](#transports).

## Transports

The SDK calls the CBWS API in one of two ways:

- **Native gRPC** through the PHP `grpc` extension. The SDK uses it when the extension is loaded.
- **gRPC-Web** over HTTPS, which needs no extension. The SDK uses it when the `grpc` extension is not loaded. The
  requests go through Guzzle, which uses the `curl` extension when it is installed.

Both send the same requests and give the same responses. The SDK sends the access token with every call as an
`authorization` header, on both transports.

To use a specific transport, give it to the client constructor, or call `withTransportType()` before the first request:

```php
use Cbws\Sdk\Common\Client;

// Use gRPC-Web, also when the grpc extension is installed but unreliable
$compute = new \Cbws\Sdk\Compute\Client('some-project', null, null, Client::TRANSPORT_GRPC_WEB);

// The same, with the setter
$compute = (new \Cbws\Sdk\Compute\Client('some-project'))->withTransportType(Client::TRANSPORT_GRPC_WEB);
```

`Client::TRANSPORT_GRPC` forces native gRPC, which fails when the extension is not loaded. `null` restores the
automatic choice.

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

#### Images and image families

An image is an operating system that machines are installed from. An image family is a named series of images, such as
all the images of one operating system release. A family resolves to its current image for a machine type, because a
family can hold different images for different machine types.

The SDK reads images and families from the client's project. To read them from another project, give that project to
the request with `withProject()`.

```php
use Cbws\Sdk\Compute\Requests\GetImageFromFamilyRequest;
use Cbws\Sdk\Compute\Requests\ListImageFamiliesRequest;

// List the images of the client's project
foreach ($compute->images()->paginate() as $image) {
    echo $image->getName(), ' ', $image->getFamily() ?? '(no family)', PHP_EOL;
}

// List the image families of another project
foreach ($compute->imageFamilies()->paginate((new ListImageFamiliesRequest())->withProject('other-project')) as $family) {
    echo $family->getName(), PHP_EOL;
}

// Get the image that a machine of type g4.2 gets from the family now. The machine type is an id, such as `g4.2`,
// or a resource name, such as `zones/nl-ein-1/types/g4.2`.
$image = $compute->imageFamilies()->getImage(
    'ubuntu-2404-lts',
    'g4.2',
    (new GetImageFromFamilyRequest())->withProject('other-project'),
);
```

`images()->get()` and `imageFamilies()->get()` take the id of the image or family and accept a request with
`withProject()` in the same way. A family from a list or `get()` also has a `getImage()` method that uses the family's
own project.
