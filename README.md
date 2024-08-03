# Session Validator Client PHP

PHP client for Emarsys session validator service

## Install

```bash
composer require emartech/session-validator-client
```

## Usage

### Validating a single MSID

```php
$client = Client::create('https://session-validator.gservice.emarsys.net', 'escher_key', 'escher_secret');

var_dump($client->isValid('msid'));
```

### Requests without Escher

For mTLS on GAP.

```php
$client = Client::create('http://session-validator-web.security');

var_dump($client->isValid('msid'));
```

### Batch validating multiple MSIDs

Returns an array of the invalid MSIDs.

```php
$client = Client::create('https://session-validator.gservice.emarsys.net', 'escher_key', 'escher_secret');

var_dump($client->filterInvalid(['msid1', 'msid2']));
```

### Caching results

```php
$client = Client::create('https://session-validator.gservice.emarsys.net', 'escher_key', 'escher_secret');
$cachedClient = CachedClient::create($client);

var_dump($cachedClient->isValid('msid'));
```

### Logging

To enable logging, add a PSR-3 compatible logger to the client

```php
use Monolog\Logger;

$client = Client::create('https://session-validator.gservice.emarsys.net', 'escher_key', 'escher_secret');
$client->setLogger(new Logger('name'));
```

### Use with CodeShip
Because of the APCu dependency, install extension before `composer install`

```bash
printf "\n" | pecl install apcu
```

### Local development

```bash
make install
make test
make style
```
