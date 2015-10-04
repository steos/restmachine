# Conditional Requests

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