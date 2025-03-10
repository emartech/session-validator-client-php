# Session Validator Client PHP

PHP client for Emarsys session validator service

## Install

```bash
composer require emartech/session-validator-client
```

## Usage

### Create client

Escher example:
```php
$client = Client::create('https://session-validator.gservice.emarsys.net', 'escher_key', 'escher_secret');
```

mTLS example:
```php
$client = Client::create('http://session-validator-web.security');
```

### Check Session Validity

`isValid` provides a function to validate user session using either a `msId` or a `sessionDataToken`.

| Name               | Type     | Throws             | Description                                  |
|--------------------|----------|--------------------|----------------------------------------------|
| `msId`             | `string` | -                  | Deprecated and will be removed in the future |
| `sessionDataToken` | `string` | `SessionDataError` |                                              |

```php
var_dump($client->isValid('msid'));
```

```php
var_dump($client->isValid('session-data-token'));
```

### Batch validating multiple MSIDs

Returns an array of the invalid MSIDs.

> Warning: The batch validation is deprecated and will be removed in the future.

```php
var_dump($client->filterInvalid(['msid1', 'msid2']));
```

### Caching results

```php
$client = Client::create('https://session-validator.gservice.emarsys.net', 'escher_key', 'escher_secret');
$cachedClient = CachedClient::create($client);

var_dump($cachedClient->isValid('msid')); // OR
var_dump($cachedClient->isValid('session-data-token'));
```

### Fetch session data

`getSessionData` provides a function to fetch user session data object using a `sessionDataToken`.

```php
const sessionData = $client->getSessionData('session-data-token');
```

### Logging

To enable logging, add a PSR-3 compatible logger to the client

```php
use Monolog\Logger;

$client->setLogger(new Logger('name'));
```

### Local development

```bash
make install
make test
make style
```
