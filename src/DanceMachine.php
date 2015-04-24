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
            'exists?' => ['if-match-exists?', 'if-match-star-for-missing?'],
            'if-match-star-for-missing?' => [Response::HTTP_PRECONDITION_FAILED, 'PUT?'],
            'PUT?' => ['put-to-different-url?', 'existed?'],
            'existed?' => ['moved-permanently?', 'post-to-missing?'],
            'moved-permanently' => [Response::HTTP_MOVED_PERMANENTLY, 'moved-temporarily?'],
            'moved-temporarily' => [Response::HTTP_TEMPORARY_REDIRECT, 'post-to-gone?'],
            'post-to-gone?' => ['can-post-to-gone?', Response::HTTP_GONE],
            'can-post-to-gone?' => ['post!', Response::HTTP_GONE],
            'post-to-missing?' => ['can-post-to-missing?', Response::HTTP_NOT_FOUND],
            'can-post-to-missing?' => ['post!', Response::HTTP_NOT_FOUND],

            'post!' => 'post-redirect?',

            'post-redirect?' => [Response::HTTP_SEE_OTHER, 'new?'],
            'new?' => [Response::HTTP_CREATED, 'respond-with-entity?'],
            'respond-with-entity?' => ['multiple-representations?', Response::HTTP_NO_CONTENT],
            'multiple-representations?' => [Response::HTTP_MULTIPLE_CHOICES, Response::HTTP_OK]

        ];
    }

    private function isHandler($node) {
        return is_string($node) && substr($node, -1) == '!';
    }

    private function isDecision($node) {
        return is_string($node) && substr($node, -1) == '?';
    }

    private function isLeafNode($node) {
        return is_int($node);
    }

    private function dispatch(Dance $resource, Song $context, $init = 'service-available?') {
        $node = $init;
        while (!$this->isLeafNode($node)) {
            if ($this->isDecision($node)) {
                list($pass, $fail) = $this->decisions[$node];
                $result = $resource($node, $context);
                $node = $result ? $pass : $fail;
            } else if ($this->isHandler($node)) {
                $resource($node, $context);
                $node = $this->decisions[$node];
            } else {
                throw new \Exception();
            }
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
        $node = $this->dispatch($resource, $context);
        //TODO response body
        return Response::create('', $node);
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
