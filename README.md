# restmachine

restmachine is a [webmachine](https://github.com/basho/webmachine) implementation for PHP.

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

## Credits

Credits go to [clojure-liberator](http://clojure-liberator.github.io/liberator/) where I extracted the decision graph
and which I heavily used as reference and documentation to understand the webmachine execution model
and the excellent [Symfony HttpFoundation](https://github.com/symfony/HttpFoundation) which RestMachine is built on. 

## Project Status

This is alpha software. A lot of the functionality is still missing. It will be buggy and there is no
stable API anything.

### TODO

- Caching and conditional requests
- Content negotiation for language, charset, encoding
- Standalone wrapper with routing
- PATCH

## License

Copyright © 2015 Stefan Oestreicher and contributors.

Distributed under the terms of the BSD-3-Clause license.
