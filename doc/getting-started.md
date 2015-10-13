# Getting Started

RestMachine is not a framework and currently does not have any support for routing so right out of the box you cannot use it standalone.
It is planned to have RestMachine be usable standalone eventually though.

For most practical purposes however you'll probably want to use it with a framework.
RestMachine is most easily integrated with Silex or Symfony since it is built on the Symfony HttpFoundation component.
With the [PSR-7 bridge](http://symfony.com/doc/current/cookbook/psr7.html) it is pretty straightforward to
[use with Laravel](http://laravel.com/docs/requests#psr7-requests) as well.
And everything else that can work with PSR-7 obviously.

## First Steps

**Create a** `Resource`:

```php
use RestMachine\Resource;

$resource = Resource::create()
  ->availableMediaTypes(['text/html'])
  ->handleOk(function($context) {
    return '<html><body>Hello</body></html>';
  });
```

**And run it through** `WebMachine`:

```php
use RestMachine\WebMachine;

$webmachine = new WebMachine();
$response = $webmachine->run($resource, $request);
```

The `$request` parameter must be a Symfony HttpFoundation Request instance.
It is optional and will be created from globals by default with its own `createFromGlobals` factory method.

## With Silex

Since Silex is built on the Symfony components you can directly pass the request object to `WebMachine`.

```php
$webmachine = new WebMachine();
$app = new Silex\Application();
$app->match('/hello', function() use ($app, $webmachine) {
  $resource = Resource::create()->handleOk('Hello World!');
  return $webmachine->run($resource, $app['request']);
});
```

You can find a small example application built with Silex in the `examples` directory.

## With Laravel 5

Add the PSR-7 bridge and RestMachine to your composer.json:

```
composer require symfony/psr-http-message-bridge zendframework/zend-diactoros 25th/restmachine
```

And then you can use it like this:

```php
use Psr\Http\Message\ServerRequestInterface;
use RestMachine\Resource;

Route::any('/hello', function(ServerRequestInterface $req) {
    $webmachine = new \RestMachine\WebMachine();
    $psr7 = new \Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory();
    $symfony = new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory();
    $response = $webmachine->run(Resource::create()->handleOk('Hello World!'), $symfony->createRequest($req));
    return $psr7->createResponse($response);
});
```

Disable CSRF for RestMachine routes by adding them to the `VerifyCsrfToken` middleware:

```php
class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
        'hello'
    ];
}
```

And see if it works:

```
$ curl http://localhost:8000/hello -i -s
HTTP/1.1 200 OK
...

Hello World!
```

```
$ curl http://localhost:8000/hello -H 'Accept: application/json' -i
HTTP/1.1 406 Not Acceptable
```

```
$ curl http://localhost:8000/hello -i -s -X POST
HTTP/1.1 405 Method Not Allowed
```