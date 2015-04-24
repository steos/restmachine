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
            'post-redirect?' => [[303, ''], 'new?'],
            'moved-temporarily?' => [[307, ''], 'post-to-gone?'],
            'valid-entity-length?' => ['is-options?', [413, 'Request entity too large.']],
            'media-type-available?' => ['accept-language-exists?', [406, 'No acceptable resource available.']],
            'known-method?' => ['uri-too-long?', [501, 'Unknown method.']],
            'modified-since?' => ['method-delete?', [304, '']],
            'etag-matches-for-if-match?' => ['if-unmodified-since-exists?', [412, 'Precondition failed.']],
            'if-match-star?' => ['if-unmodified-since-exists?', 'etag-matches-for-if-match?'],
            'post-to-existing?' => ['post!', 'put-to-existing?'],
            'if-modified-since-exists?' => ['if-modified-since-valid-date?', 'method-delete?'],
            'method-put?' => ['put-to-different-url?', 'existed?'],
            'can-put-to-missing?' => ['conflict?', [501, 'Not implemented.']],
            'can-post-to-gone?' => ['post!', [410, 'Resource is gone.']],
            'is-options?' => [[200, ''], 'accept-exists?'],
            'post-to-missing?' => ['can-post-to-missing?', [404, 'Resource not found.']],
            'put-to-different-url?' => [[301, ''], 'can-put-to-missing?'],
            'put!' => 'new?',
            'if-match-exists?' => ['if-match-star?', 'if-unmodified-since-exists?'],
            'language-available?' => ['accept-charset-exists?', [406, 'No acceptable resource available.']],
            'if-match-star-exists-for-missing?' => [[412, 'Precondition failed.'], 'method-put?'],
            'processable?' => ['exists?', [422, 'Unprocessable entity.']],
            'valid-content-header?' => ['known-content-type?', [501, 'Not implemented.']],
            'if-none-match-star?' => ['if-none-match?', 'etag-matches-for-if-none?'],
            'conflict?' => [[409, 'Conflict.'], 'put!'],
            'put-to-existing?' => ['conflict?', 'multiple-representations?'],
            'allowed?' => ['valid-content-header?', [403, 'Forbidden.']],
            'existed?' => ['moved-permanently?', 'post-to-missing?'],
            'service-available?' => ['known-method?', [503, 'Service not available.']],
            'unmodified-since?' => [[412, 'Precondition failed.'], 'if-none-match-exists?'],
            'delete-enacted?' => ['respond-with-entity?', [202, 'Accepted']],
            'accept-language-exists?' => ['language-available?', 'accept-charset-exists?'],
            'if-none-match-exists?' => ['if-none-match-star?', 'if-modified-since-exists?'],
            'charset-available?' => ['accept-encoding-exists?', [406, 'No acceptable resource available.']],
            'method-patch?' => ['patch!', 'post-to-existing?'],
            'accept-encoding-exists?' => ['encoding-available?', 'processable?'],
            'exists?' => ['if-match-exists?', 'if-match-star-exists-for-missing?'],
            'method-delete?' => ['delete!', 'method-patch?'],
            'can-post-to-missing?' => ['post!', [404, 'Resource not found.']],
            'known-content-type?' => ['valid-entity-length?', [415, 'Unsupported media type.']],
            'moved-permanently?' => [[301, ''], 'moved-temporarily?'],
            'if-modified-since-valid-date?' => ['modified-since?', 'method-delete?'],
            'malformed?' => [[400, 'Bad request.'], 'authorized?'],
            'patch!' => 'respond-with-entity?',
            'if-unmodified-since-valid-date?' => ['unmodified-since?', 'if-none-match-exists?'],
            'multiple-representations?' => [[300, ''], [200, 'OK']],
            'delete!' => 'delete-enacted?',
            'post!' => 'post-redirect?',
            'etag-matches-for-if-none?' => ['if-none-match?', 'if-modified-since-exists?'],
            'respond-with-entity?' => ['multiple-representations?', [204, '']],
            'method-allowed?' => ['malformed?', [405, 'Method not allowed.']],
            'uri-too-long?' => [[414, 'Request URI too long.'], 'method-allowed?'],
            'if-unmodified-since-exists?' => ['if-unmodified-since-valid-date?', 'if-none-match-exists?'],
            'post-to-gone?' => ['can-post-to-gone?', [410, 'Resource is gone.']],
            'accept-charset-exists?' => ['charset-available?', 'accept-encoding-exists?'],
            'encoding-available?' => ['processable?', [406, 'No acceptable resource available.']],
            'authorized?' => ['allowed?', [401, 'Not authorized.']],
            'accept-exists?' => ['media-type-available?', 'accept-language-exists?'],
            'if-none-match?' => [[304, ''], [412, 'Precondition failed.']],
            'new?' => [[201, ''], 'respond-with-entity?'],
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
