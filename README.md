[![PHP version](https://img.shields.io/badge/PHP-%3E%3D5.5-8892BF.svg?style=flat-square)](http://php.net)
[![Latest Version](https://img.shields.io/packagist/vpre/juliangut/cacheware.svg?style=flat-square)](https://packagist.org/packages/juliangut/cacheware)
[![License](https://img.shields.io/github/license/juliangut/cacheware.svg?style=flat-square)](https://github.com//cacheware/blob/master/LICENSE)

[![Build status](https://img.shields.io/travis/juliangut/cacheware.svg?style=flat-square)](https://travis-ci.org/juliangut/cacheware)
[![Style](https://styleci.io/repos/59418987/shield)](https://styleci.io/repos/59418987)
[![Code Quality](https://img.shields.io/scrutinizer/g/juliangut/cacheware.svg?style=flat-square)](https://scrutinizer-ci.com/g/juliangut/cacheware)
[![Code Coverage](https://img.shields.io/coveralls/juliangut/cacheware.svg?style=flat-square)](https://coveralls.io/github/juliangut/cacheware)
[![Total Downloads](https://img.shields.io/packagist/dt/juliangut/cacheware.svg?style=flat-square)](https://packagist.org/packages/juliangut/cacheware)

# CacheWare

A PSR7 cache headers management middleware.

This middleware must be run *before* `session_start` has been called so it can prevent PHP session mechanism from automatically send any kind of header to the client (including session cookie and caching).

> You can use this middleware with [juliangut/sessionware](https://github.com/juliangut/sessionware) which will automatically handle session management.

## Installation

### Composer

```
composer require juliangut/cacheware
```

## Usage

```php
require 'vendor/autoload.php';

use \Jgut\Middleware\CacheWare

$configuration = [
  'limiter' => 'private',
  'expire' => 1800, // 30 minutes
];

$cacheMiddleware = new CacheWare($configuration);

// Get $request and $response from PSR7 implementation
$request = new Request();
$response = new Response();

$response = $cacheMiddleware($request, $response, function() { });

// Response has corresponding cache headers for private cache
```

Integrated on a Middleware workflow:

```php
require 'vendor/autoload.php';

use \Jgut\Middleware\CacheWare

$app = new \YourMiddlewareAwareApplication();
$app->addMiddleware(new CacheWare(['limiter' => 'nocache']));
$app->run();
```

### Config

```php
$cacheMiddleware = new CacheWare([
  'limiter' => null
  'expire' => 180,
]);
```

#### limiter

Selects cache limiter type. It's values can be `public`, `private`, `private_no_expire` or `nocache`. If not provided value defined in ini_set `session.cache_limiter` will be automatically used (normally 'nocache').

If you want to completely disable cache headers give limiter a value of `null`.

#### expire

Sets the time in seconds for caching. If not provided value defined in ini_set `session.cache_expire` will be automatically used (normally 180). This setting is ignore when using `nocache` limiter.

## Contributing

Found a bug or have a feature request? [Please open a new issue](https://github.com/juliangut/cacheware/issues). Have a look at existing issues before.

See file [CONTRIBUTING.md](https://github.com/juliangut/cacheware/blob/master/CONTRIBUTING.md)

## License

See file [LICENSE](https://github.com/juliangut/cacheware/blob/master/LICENSE) included with the source code for a copy of the license terms.
