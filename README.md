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

RestMachine starts with **service-available?** and just walks the tree until it hits a handler.

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
service-available? and things like that all the time.

### Resource Builder

For actually creating a resource specification RestMachine provides the Resource class which internally
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
(so isServiceAvailable is superfluous here because it defaults to true).

#### Directives

Directives are entries in the resource specification that don't correspond to nodes in the decision tree.
Those are used as configuration of default decisions. For example:

```php

Resource::create()->allowedMethods(['GET']);

```

This array will be used by the default implementation of method-allowed? which will try to match the actual
request method with one in the array.

Directives:

- availableMediaTypes
- allowedMethods
- lastModified
- etag

## Credits

Credits go to [clojure-liberator](http://clojure-liberator.github.io/liberator/) where we extracted the decision graph
and which we heavily used as reference and documentation to understand the webmachine execution model
and the excellent [Symfony HttpFoundation](https://github.com/symfony/HttpFoundation) which RestMachine is built on. 

## Project Status

This is alpha software. Some functionality is still missing.
You will find bugs and the API may still change radically.

### TODO

- Caching and conditional requests
- Content negotiation for language, charset, encoding
- Standalone wrapper with routing
- PATCH method

## License

Copyright © 2015 Stefan Oestreicher and contributors.

Distributed under the terms of the BSD-3-Clause license.
