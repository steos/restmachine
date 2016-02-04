# restmachine

[![Build Status](https://travis-ci.org/steos/restmachine.svg?branch=master)](https://travis-ci.org/steos/restmachine)

restmachine is a [webmachine](https://github.com/webmachine/webmachine) implementation for PHP.

Webmachine brings HTTP semantic awareness to your application. It allows you to declaratively
specify dynamic HTTP resources so you don't have to worry about implementation details.

## Example

```php
Resource::create(self::defaults())
    ->allowedMethods(['GET', 'PUT', 'DELETE'])
    ->isProcessable(self::validator())
    ->canPutToMissing(false)
    ->isNew(false)
    ->isRespondWithEntity(function(Context $context) {
        return $context->getRequest()->isMethod('PUT');
    })
    ->exists(function($context) use ($db, $id) {
        return Todo::exists($db, $id);
    })
    ->put(function($context) use ($db, $id) {
        Todo::update($db, $id, $context->entity);
    })
    ->delete(function($context) use ($db, $id) {
        Todo::delete($db, $id);
    })
    ->handleOk(function(Context $context) use ($db, $id) {
        return Todo::fetchOne($db, $id);
    });
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

restmachine currently requires PHP >= 5.4.

## Documentation

- [Getting Started](doc/getting-started.md)
- [How It Works](doc/how-it-works.md)
- [Serialization](doc/serialization.md)
- [Content Negotiation](doc/content-negotiation.md)
- [Conditional Requests](doc/conditional-requests.md)
- [Debugging](doc/debugging.md)

### Examples

A fully baked example app using Silex can be found in the `examples` directory.
It uses a sqlite database therefore you will need the sqlite PDO driver to run it.

```
# just serve the silex.php entry point through a webserver
$ php -S 0.0.0.0:8080 examples/public/silex.php

# then you can use the "/todos" resources
$ curl http://localhost:8080/todos -i -s -X POST -d \
    '{"text":"switch to restmachine"}'
$ curl http://localhost:8080/todos/1 -i -s -X PUT -d \
    '{"text":"switch to restmachine", "done":true}'
$ curl http://localhost:8080/todos/1 -i -s -X DELETE
$ curl http://localhost:8080/todos/1 -i -s
```

## Credits

Credits go to

- [clojure-liberator](http://clojure-liberator.github.io/liberator/)
  where we extracted the decision graph and which we heavily used as reference and documentation to understand the webmachine execution model

- [Symfony HttpFoundation](https://github.com/symfony/HttpFoundation)
  which RestMachine is built on.

## Project Status

This is beta software. Some functionality is still missing.
There will be bugs. The API may still change, but should be fairly stable.

### TODO

- Content negotiation for language, charset, encoding
- Standalone wrapper with routing
- PATCH method
- handle RFC850/1036 and ANSI C's asctime() format as per rfc 2616 (`Utils::parseHttpDate`)

## License

Copyright © 2015 Stefan Oestreicher and contributors.

Distributed under the terms of the BSD-3-Clause license.
