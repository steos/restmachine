# restmachine

restmachine is a [webmachine](https://github.com/basho/webmachine) implementation for PHP.

Webmachine brings HTTP semantic awareness to your application. It allows you to declaratively
specify dynamic HTTP resources and frees you from having to worry about correct implementation of
the HTTP protocol and its semantics.

## Example

```php
  Resource::create()
    ->availableMediaTypes(['application/json', 'application/php'])
    ->allowedMethods(['GET', 'PUT'])
    ->isExists(function($context) {
        $context->entity = $db->find($context->param('id'));
        return $context->entity !== null;
    })
    ->isMalformed(function($context) {
       if ($context->getRequest()->isMethod('PUT')) {
         $context->requestData = json_decode($context->getRequest()->getBody());
         return json_last_error();
       }
       return false;
    })
    ->isProcessable(function($context) {
        return $context->getRequest()->getMethod() != 'PUT'
          || $validator->isValid($context->requestData);
    })
    ->put(function($context) {
        $context->entity = $db->update($context->param('id'), $context->requestData);
    })
    ->handleOk(function($context) {
        return $context->entity;
    })
```

### Install

With composer:

```
{
  "require": {
    "steos/restmachine": "dev-master"
  }
}
```

restmachine currently requires PHP >= 5.5.

## Documentation

### Decisions, Decisions

RestMachine is built on a simple decision tree. This tree can have three different kinds of nodes. Every node
has a name and is represented as just a key-value-pair in an array.

```php

$decisions = [
  // decision
  'malformed?' => ['handle-malformed', 'authorized?'],

  // action
  'post!' => 'post-redirect?',

  // handler
  'handle-malformed' => 400,
];

```

RestMachine starts with `service-available?` and just walks the tree until it hits a handler.

For every decision node it looks in the resource specification for that node and just evaluates it.

### Resource Specification

Resource specification example:

```php

$resource = [
    'malformed?' => function($context) {
        // do some validation on the request body here
        // you can get the request via the $context param
        return false;
    },
    'service-available?' => true
];

```

The resource specification values can be scalars or callable. If a node is not present it just evaluates
to false, if it's a scalar it evaluates that scalar in a truthy context, and if it's a callable it uses the
return value of that callable as the decision result.

RestMachine has sensible default values for most decisions so you don't have to specify
`service-available?` and things like that all the time.

### Resource Builder

For actually creating a resource specification RestMachine provides the `Resource` class which internally
just builds up an array like the one shown above, so your IDE can better help you remember
all those node names.

```php

$resource = Resource::create()
  ->isMalformed(function($context) {
      return false;
  })
  ->isServiceAvailable(true)

```

This creates the same resource array as shown above except it will be merged with the builtin defaults
(so `isServiceAvailable(true)` is superfluous here because it defaults to true).

#### Directives

Directives are entries in the resource specification that don't correspond to nodes in the decision tree.
Those are used as configuration of default decisions. Like any other value in the resource specification
it can be a scalar or a callable so the value can depend on the actual request. For example:

```php
// those are both equivalent
Resource::create()->allowedMethods(['GET']);
// when using a callable you can inspect the context (which also gives
// you access to the request object) to decide on the return value
Resource::create()->allowedMethods(function($context) {
  return ['GET'];
})
```

This array will be used by the default implementation of `method-allowed?` which will try to match the actual
request method with one in the array.

##### availableMediaTypes

The value for this directive must evaluate to an array of strings. This array is used by the default implementation
of `media-type-available?` to negotiate the content type of the response.

Example:

```php
Resource::create()->availableMediaTypes(['application/json', 'text/csv']);
```

##### allowedMethods

The value for this directive must also evaluate to an array of strings. This array should contain a list of 
allowed HTTP methods. RestMachine will try to match those against the actual HTTP request method in its 
default implementation of the `method-allowed?` decision.

Example:

```php
Resource::create()->allowedMethods(['GET', 'HEAD']);
```

##### lastModified

The value for this directive must be a `DateTime` instance. It is used by the default implementations
of the `modified-since?` and `unmodified-since?` decisions to implement
`If-Modified-Since` and `If-Unmodified-Since` semantics.

```php
Resource::create()->lastModified(new \DateTime('2015-10-01'));
```

##### etag

The etag value must evaluate to a string. This value is used to implement `If-Match` and `If-None-Match` semantics by
the default implementations of the `etag-matches-for-if-none?` and `etag-matches-for-if-match?` decisions.

```php
Resource::create()->etag('aXs3f');
```

### Content Negotiation

RestMachine currently implements content negotiation only for the media type. If the client sends an appropriate
`Accept` header it will negotiate the media type of the response and respond with an appropriate `Vary` header.

Example:

```php
Resource::create()
    ->availableMediaTypes(['application/json', 'application/php'])
    ->handleOk(function($context) {
        return ['hi there'];
    })
```

```
$ curl http://localhost -H 'Accept: application/php' -i -s
HTTP/1.0 200 OK
Vary: Content-Type
Last-Modified: Sun, 28 Jun 2015 00:00:00 +0200
Content-Length: 25
Connection: close
Content-Type: application/php

a:1:{i:0;s:8:"hi there";}
```

### Conditional Requests

RestMachine currently supports conditional requests for `If-Modified-Since`, `If-Unmodified-Since`, `If-Match` 
and `If-None-Match`.

Example:

```php
 Resource::create($defaults)
    ->lastModified(new \DateTime('2015-06-28', new \DateTimeZone('Europe/Vienna')))
    ->etag('aXs3f')
```

`If-Modified-Since`-Example:

```
curl http://localhost -i -s -H 'If-Modified-Since: Sun, 28 Jun 2015 00:00:00 +0200'
HTTP/1.0 304 Not Modified
Vary: Content-Type
```

`If-Match`-Example:

```
$ curl http://localhost/dancery/example1.php -i -s -H 'If-Match: foo'
HTTP/1.0 412 Precondition Failed
```

## Credits

Credits go to [clojure-liberator](http://clojure-liberator.github.io/liberator/) where we extracted the decision graph
and which we heavily used as reference and documentation to understand the webmachine execution model
and the excellent [Symfony HttpFoundation](https://github.com/symfony/HttpFoundation) which RestMachine is built on. 

## Project Status

This is alpha software. Some functionality is still missing.
You will find bugs and the API may still change radically.

### TODO

- Content negotiation for language, charset, encoding
- Standalone wrapper with routing
- PATCH method

## License

Copyright © 2015 Stefan Oestreicher and contributors.

Distributed under the terms of the BSD-3-Clause license.
