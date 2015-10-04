# Serialization

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

## Custom Serializer

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