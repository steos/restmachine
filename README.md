# Dancery

Dancery is a [webmachine](https://github.com/basho/webmachine) implementation for PHP.

## Example

```php
use Dancery\Dance;
use Dancery\DanceMachine;

$danceMachine = new DanceMachine();
$danceMachine->perform(
  Dance::create()
    ->availableMediaTypes('application/json', 'application/php')
    ->allowedMethods('GET', 'PUT')
    ->isExisting(function($context) {
        $context->entity = $db->find($context->param('id'));
        return $entity !== null;
    })
    ->isMalformed(function($context) {
       if ($context->request()->getMethod() == 'PUT') {
         $context->requestData = json_decode($context->request()->getBody());
         return json_last_error();
       }
       return false;
    })
    ->isProcessable(function($context) {
        return $context->request()->getMethod() != 'PUT'
          || $validator->isValid($context->requestData);
    })
    ->get('entity')
    ->put(function($context) {
        $db->update($context->param('id'), $context->requestData);
    })
);
```

## Install


```
{
  "require": {
    "steos/dancery": "dev-master"
  }
}
```

## Minimum PHP Version

Dancery currently requires PHP 5.5 at a minimum.

## Credits

Credits go to [clojure-liberator](http://clojure-liberator.github.io/liberator/) where I extracted the decision graph
and which I heavily used as reference and documentation to understand the webmachine execution model.

## Project Status

Pre-alpha at best. Contributions, suggestions, flames are welcome.

## License

Copyright © 2015 Stefan Oestreicher and contributors.

Distributed under the terms of the BSD-3-Clause license.
