# Content Negotiation

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