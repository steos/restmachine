# How It Works

## Decisions, Decisions

RestMachine is built on a simple decision tree. This tree was taken verbatim from liberator so you can
just refer to [this graph](https://clojure-liberator.github.io/liberator/assets/img/decision-graph.svg) to see
it in all its glory.
In RestMachine it is represented as an array where every key-value-pair is a node:

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

## Resource Specification

Internally a resource specification is just an array mapping nodes to values:

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

Every node can map to any value.
If the value is `callable` it will be invoked with a `$context` parameter and its return value will be used.
Otherwise the value will be used as is. How it is used depends on the node type:

 - **decision node** values will be used in a truthy context to decide on the next node.
 - **handlers** are leaf nodes. The value is serialized if necessary and used as response body.
 - **actions** are for side effects only so the only sensible thing to use are `callable`'s. Their return value doesn't matter.

## Resource Builder

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

### Directives

Directives are entries in the resource specification that don't correspond to nodes in the decision tree.
Those are used as configuration of default decisions.

#### availableMediaTypes
Type: `string[]`

Used by the default implementation of `media-type-available?` to negotiate the content type of the response.
The negotiated type is set on the context (`setMediaType`, `getMediaType`).

Example:

```php
Resource::create()->availableMediaTypes(['application/json', 'text/csv']);
```

#### allowedMethods
Type: `string[]`

A list of allowed HTTP methods.RestMachine will try to match those against the actual HTTP request method in its 
default implementation of the `method-allowed?` decision.

Example:

```php
Resource::create()->allowedMethods(['GET', 'HEAD']);
```

#### lastModified
Type: `DateTime`

Used by the default implementations of the `modified-since?` and `unmodified-since?`
decisions to implement `If-Modified-Since` and `If-Unmodified-Since` semantics.

```php
Resource::create()->lastModified(new \DateTime('2015-10-01'));
```

#### etag
Type: `string`

Used to implement `If-Match` and `If-None-Match` semantics by
the default implementations of the `etag-matches-for-if-none?` and `etag-matches-for-if-match?` decisions.

```php
Resource::create()->etag('aXs3f');
```

## Context

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
