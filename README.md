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
Vary: Accept
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
$ curl http://localhost -i -s -H 'If-Modified-Since: Sun, 28 Jun 2015 00:00:00 +0200'
HTTP/1.0 304 Not Modified
Vary: Accept
```

`If-Match`-Example:

```
$ curl http://localhost -i -s -H 'If-Match: foo'
HTTP/1.0 412 Precondition Failed
```

### Context

RestMachine provides a context object that lives for the duration of request execution. This context
is passed to all callable values and can be used to obtain the actual HTTP request object.
Furthermore it is used to gather information during decision functions that may be relevant later.
RestMachine internally uses this context for content negotiation and conditional requests. You can put
arbitrary values into the context to communicate with later running decisions, actions or handlers.

Example:

```php
Resource::create()
    ->isMalformed(function($context) {
        if ($context->getRequest()->isMethod('POST')) {
            $requestBody = $context->getRequest()->getContent();
            // validate request body
            if (!isValid($requestBody)) {
                return true;
            }
            // parse request body
            $context->entity = parseRequestBody($requestBody);
        }
        return false;
    })
    ->post(function($context) {
        // do something with the entity we put into context
        // during the malformed? decision
        $db->insert($context->entity);
    });
```

### Serialization

RestMachine supports multiple serialization formats and you can easily provide your own serializer.
Return values of handler functions will automatically be serialized according to the negotiated media type if a serializer for that media type is available. If the handler returns a string no serialization will be performed.

RestMachine has builtin serializers for:
- `application/json` using `json_encode`
- `text/php` using `var_export`
- `application/php` using `serialize`
- `text/plain` using `strval` (i.e. it just coerces the value to a string)

Example:

```php
Resource::create()
  ->availableMediaTypes(['application/php', 'application/json'])
  ->handleOk(function($context) {
    return ['hi there'];
  });
```

```
$ curl http://localhost -i -s
HTTP/1.0 200 OK
Vary: Accept
Content-Type: application/json

["hi there"]
```

```
$ curl http://localhost -i -s -H 'Accept: application/php'
HTTP/1.0 200 OK
Vary: Accept
Content-Type: application/php

a:1:{i:0;s:8:"hi there";}
```

#### Custom Serializer

A RestMachine serializer is just a function that accepts an arbitrary PHP value as its only argument and returns a serialized representation of that value. Example:

```php
$webMachine = new RestMachine\WebMachine();
$webMachine->installSerializer('text/csv', function($value) {
  // we're assuming that $value is always a two-dimensional array
  $stream = fopen('php://temp', 'w');
  foreach ($value as $row) {
    fputcsv($stream, $row);
  }
  rewind($stream);
  return stream_get_contents($stream);
});
```

```php
Resource::create()
  ->availableMediaTypes(['application/php', 'application/json', 'text/csv'])
  ->handleOk(function($context) {
    return [['row1 col1', 'row1 col2', 'row1 col3']
           ,['row2 col1', 'row2 col2', 'row2 col3']
           ,['row3 col1', 'row3 col2', 'row3 col3']];
  });
```

```
$ curl http://localhost -i -s -H 'Accept: text/csv'
HTTP/1.0 200 OK
Vary: Accept
Content-Type: text/csv;charset=UTF-8

"row1 col1","row1 col2","row1 col3"
"row2 col1","row2 col2","row2 col3"
"row3 col1","row3 col2","row3 col3"
```

### Debugging

Sometimes it can be hard to figure out how RestMachine arrived at a specific handler and response. To make it easier
you can enable execution tracing.

```php
$webMachine->enableTrace();
```

If tracing is enabled RestMachine will remember the path it took through the decision graph and output `X-RestMachine-Trace` headers:

```
X-RestMachine-Trace: service-available?             -> true
X-RestMachine-Trace: known-method?                  -> true
X-RestMachine-Trace: uri-too-long?                  -> null
X-RestMachine-Trace: method-allowed?                -> true
X-RestMachine-Trace: malformed?                     -> false
X-RestMachine-Trace: authorized?                    -> true
X-RestMachine-Trace: allowed?                       -> true
X-RestMachine-Trace: valid-content-header?          -> true
X-RestMachine-Trace: known-content-type?            -> true
X-RestMachine-Trace: valid-entity-length?           -> true
X-RestMachine-Trace: is-options?                    -> false
X-RestMachine-Trace: accept-exists?                 -> true
X-RestMachine-Trace: media-type-available?          -> true
X-RestMachine-Trace: accept-language-exists?        -> null
X-RestMachine-Trace: accept-charset-exists?         -> null
X-RestMachine-Trace: accept-encoding-exists?        -> null
X-RestMachine-Trace: processable?                   -> true
X-RestMachine-Trace: exists?                        -> true
X-RestMachine-Trace: if-match-exists?               -> false
X-RestMachine-Trace: if-unmodified-since-exists?    -> false
X-RestMachine-Trace: if-none-match-exists?          -> false
X-RestMachine-Trace: if-modified-since-exists?      -> false
X-RestMachine-Trace: method-delete?                 -> false
X-RestMachine-Trace: method-patch?                  -> false
X-RestMachine-Trace: post-to-existing?              -> false
X-RestMachine-Trace: put-to-existing?               -> false
X-RestMachine-Trace: multiple-representations?      -> null
X-RestMachine-Trace: handle-ok
```

## Credits

Credits go to

- [clojure-liberator](http://clojure-liberator.github.io/liberator/)
  where we extracted the decision graph and which we heavily used as reference and documentation to understand the webmachine execution model

- [Symfony HttpFoundation](https://github.com/symfony/HttpFoundation)
  which RestMachine is built on.
   
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
