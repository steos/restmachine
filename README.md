# restmachine

restmachine is a [webmachine](https://github.com/basho/webmachine) implementation for PHP.

## Example

```php
use RestMachine\Resource;
use RestMachine\WebMachine;

$webMachine = new WebMachine();
$webMachine->run(
  Resource::create()
    ->availableMediaTypes('application/json', 'application/php')
    ->allowedMethods('GET', 'PUT')
    ->isExisting(function($context) {
        $context->entity = $db->find($context->param('id'));
        return $context->entity !== null;
    })
    ->isMalformed(function($context) {
       if ($context->getRequest()->getMethod() == 'PUT') {
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
);
```

## Install


```
{
  "require": {
    "steos/restmachine": "dev-master"
  }
}
```

## Minimum PHP Version

restmachine currently requires PHP 5.5 at a minimum.

## Credits

Credits go to [clojure-liberator](http://clojure-liberator.github.io/liberator/) where I extracted the decision graph
and which I heavily used as reference and documentation to understand the webmachine execution model.

## Project Status

Pre-alpha at best. Contributions, suggestions, flames are welcome.

## License

Copyright © 2015 Stefan Oestreicher and contributors.

Distributed under the terms of the BSD-3-Clause license.
