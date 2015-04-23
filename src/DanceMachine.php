<?php

namespace Dancery;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The actual webmachine logic.
 */
class DanceMachine {
    private $serializers;
    private $decisions;

    function __construct() {
        $this->serializers = [
            'application/json' => 'json_encode',
            'text/plain' => 'strval'
        ];

        $this->decisions = [
            'service-available?' => ['known-method?', Response::HTTP_SERVICE_UNAVAILABLE],
            'known-method?' => ['uri-too-long?', Response::HTTP_NOT_IMPLEMENTED],
            'uri-too-long?' => [Response::HTTP_REQUEST_URI_TOO_LONG, 'method-allowed?'],
            'method-allowed?' => ['malformed?', Response::HTTP_METHOD_NOT_ALLOWED],
            'malformed?' => [Response::HTTP_BAD_REQUEST, 'authorized?'],
            'authorized?' => ['forbidden?', Response::HTTP_UNAUTHORIZED],
            'forbidden?' => [Response::HTTP_FORBIDDEN, 'unknown-content-headers?'],
            'unknown-content-headers?' => [Response::HTTP_NOT_IMPLEMENTED, 'known-content-type?'],
            'known-content-type?' => ['request-entity-too-large?', Response::HTTP_UNSUPPORTED_MEDIA_TYPE],
            'request-entity-too-large?' => [Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'OPTIONS?'],
            'OPTIONS?' => [Response::HTTP_OK, 'accept?'],
            'accept?' => ['acceptable-media-type-available?', 'accept-language?'],
            'acceptable-media-type-available?' => ['accept-language?', Response::HTTP_NOT_ACCEPTABLE],
            'accept-language?' => ['acceptable-language-available?', 'accept-charset?'],
            'acceptable-language-available?' => ['accept-charset?', Response::HTTP_NOT_ACCEPTABLE],
            'accept-charset?' => ['acceptable-charset-available?', 'accept-encoding?'],
            'acceptable-charset-available?' => ['accept-encoding?', Response::HTTP_NOT_ACCEPTABLE],
            'accept-encoding?' => ['acceptable-encoding-available?', 'exists?'],
            'acceptable-encoding-available?' => ['exists?', Response::HTTP_NOT_ACCEPTABLE],

        ];
    }

    private function dispatch(Dance $resource, Song $context, $init) {
        $node = $init;
        while (is_string($node)) {
            list($pass, $fail) = $this->decisions[$node];
            $result = $resource($node, $context);
            $node = $result ? $pass : $fail;
        }
        return $node;
    }

    /**
     * @param \Dancery\Dance $resource
     * @param Request $request
     * @return Response
     */
    function perform(Dance $resource, Request $request = null) {
        $context = new Song($request ?: Request::createFromGlobals());
        $status = $this->dispatch($resource, $context, 'isServiceAvailable');
        //TODO handlers
        return Response::create('', $status);
    }

    function serialize($value, $mediaType) {
        $serializer = @$this->serializers[$mediaType];
        if ($serializer) {
            return call_user_func($serializer, $value);
        }
        throw new \RuntimeException("no serializer available for $mediaType");
    }

    function installSerializer($mediaType, callable $f) {
        $this->serializers[$mediaType] = $f;
    }
}
