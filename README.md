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

## Credentials

When you do not give credentials to a client, the SDK uses the first of these that it finds:

1. An access token in the `CBWS_ACCESS_TOKEN` environment variable.
2. The service account key file or external account configuration that the `CBWS_CREDENTIALS` environment variable
   names.
3. The service account key file `~/.config/cbws/cbws.json`.
4. The CBWS CLI configuration, `~/.cbws.yaml`, or the file that `CBWS_CONFIG_FILE` names.

Use an access token when a different system gets the token for you, for example a pipeline that reads it from Vault.
The SDK does not refresh an access token. Supply a new token before the token expires. An access token does not
identify a project, so give the project to the client:

```php
use Cbws\Sdk\Auth\Credentials;

// From the CBWS_ACCESS_TOKEN environment variable
$compute = new \Cbws\Sdk\Compute\Client('some-project');

// From a value that you supply
$compute = new \Cbws\Sdk\Compute\Client('some-project', Credentials::fromAccessToken($token));
```

### External accounts

A workload that runs outside CBWS, such as a GitLab CI job, can get CBWS tokens without a service account key. The SDK
sends a token from the platform of the workload, the subject token, to CBWS STS. When the configuration names a service
account, the SDK then sends the STS token to IAM, and IAM returns an access token for that account. The configuration
file is the same one that the Go SDK reads. Set `CBWS_CREDENTIALS` to its path:

```json
{
  "type": "external_account",
  "audience": "projects/my-project/workloadIdentityPools/ci/providers/gitlab",
  "subject_token_type": "urn:ietf:params:oauth:token-type:jwt",
  "service_account_impersonation": "projects/my-project/serviceAccounts/deploy@my-project.iam-sa.cbws.xyz",
  "credential_source": {
    "file": "/builds/.cbws-id-token"
  }
}
```

The `credential_source` can be a `file`, a `url` with `headers`, or an `executable` with a `command` and a
`timeout_millis`. Each one can have a `format` of `{"type": "text"}` or
`{"type": "json", "subject_token_field_name": "value"}`. The SDK runs an executable only when
`CBWS_EXTERNAL_ACCOUNT_ALLOW_EXECUTABLES=1` is set.

The SDK reads the subject token again for each exchange, because CI tokens and projected tokens rotate. It does not put
the subject token or the tokens that it gets into an exception message.

To supply the subject token in code, implement `SubjectTokenSupplier`:

```php
use Cbws\Sdk\Auth\Credentials;
use Cbws\Sdk\Auth\ExternalAccount\Config;

$config = new Config(
    'projects/my-project/workloadIdentityPools/ci/providers/gitlab',
    Config::TOKEN_TYPE_JWT,
    $supplier,
    serviceAccountImpersonation: 'projects/my-project/serviceAccounts/deploy@my-project.iam-sa.cbws.xyz',
);

$compute = new \Cbws\Sdk\Compute\Client(null, Credentials::fromExternalAccount($config, ['cbws.compute']));
```

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
