<?php

namespace Dancery;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The actual webmachine logic.
 */
class DanceMachine {
    private $serializers;
    private $graph;

    function __construct() {
        $this->serializers = [
            'application/json' => 'json_encode',
            'text/plain' => 'strval',
            'text/php' => function($val) {
                return var_export($val, true);
            },
            'application/php' => 'serialize'
        ];

        $this->graph = [
            'handle-ok' => 200,
            'post-redirect?' => ['handle-see-other', 'new?'],
            'moved-temporarily?' => ['handle-moved-temporarily', 'post-to-gone?'],
            'valid-entity-length?' => ['is-options?', 'handle-request-entity-too-large'],
            'media-type-available?' => ['accept-language-exists?', 'handle-not-acceptable'],
            'known-method?' => ['uri-too-long?', 'handle-unknown-method'],
            'modified-since?' => ['method-delete?', 'handle-not-modified'],
            'etag-matches-for-if-match?' => ['if-unmodified-since-exists?', 'handle-precondition-failed'],
            'handle-not-found' => 404,
            'if-match-star?' => ['if-unmodified-since-exists?', 'etag-matches-for-if-match?'],
            'post-to-existing?' => ['post!', 'put-to-existing?'],
            'if-modified-since-exists?' => ['if-modified-since-valid-date?', 'method-delete?'],
            'handle-uri-too-long' => 414,
            'method-put?' => ['put-to-different-url?', 'existed?'],
            'handle-request-entity-too-large' => 413,
            'can-put-to-missing?' => ['conflict?', 'handle-not-implemented'],
            'can-post-to-gone?' => ['post!', 'handle-gone'],
            'is-options?' => ['handle-options', 'accept-exists?'],
            'post-to-missing?' => ['can-post-to-missing?', 'handle-not-found'],
            'handle-gone' => 410,
            'put-to-different-url?' => ['handle-moved-permanently', 'can-put-to-missing?'],
            'put!' => 'new?',
            'handle-accepted' => 202,
            'if-match-exists?' => ['if-match-star?', 'if-unmodified-since-exists?'],
            'language-available?' => ['accept-charset-exists?', 'handle-not-acceptable'],
            'if-match-star-exists-for-missing?' => ['handle-precondition-failed', 'method-put?'],
            'processable?' => ['exists?', 'handle-unprocessable-entity'],
            'valid-content-header?' => ['known-content-type?', 'handle-not-implemented'],
            'if-none-match-star?' => ['if-none-match?', 'etag-matches-for-if-none?'],
            'conflict?' => ['handle-conflict', 'put!'],
            'handle-precondition-failed' => 412,
            'put-to-existing?' => ['conflict?', 'multiple-representations?'],
            'allowed?' => ['valid-content-header?', 'handle-forbidden'],
            'existed?' => ['moved-permanently?', 'post-to-missing?'],
            'handle-created' => 201,
            'service-available?' => ['known-method?', 'handle-service-not-available'],
            'handle-forbidden' => 403,
            'unmodified-since?' => ['handle-precondition-failed', 'if-none-match-exists?'],
            'handle-options' => 200,
            'delete-enacted?' => ['respond-with-entity?', 'handle-accepted'],
            'accept-language-exists?' => ['language-available?', 'accept-charset-exists?'],
            'handle-unauthorized' => 401,
            'handle-unprocessable-entity' => 422,
            'if-none-match-exists?' => ['if-none-match-star?', 'if-modified-since-exists?'],
            'handle-not-acceptable' => 406,
            'charset-available?' => ['accept-encoding-exists?', 'handle-not-acceptable'],
            'handle-unsupported-media-type' => 415,
            'handle-not-implemented' => 501,
            'method-patch?' => ['patch!', 'post-to-existing?'],
            'accept-encoding-exists?' => ['encoding-available?', 'processable?'],
            'handle-unknown-method' => 501,
            'handle-multiple-representations' => 300,
            'exists?' => ['if-match-exists?', 'if-match-star-exists-for-missing?'],
            'handle-moved-temporarily' => 307,
            'method-delete?' => ['delete!', 'method-patch?'],
            'handle-not-modified' => 304,
            'can-post-to-missing?' => ['post!', 'handle-not-found'],
            'handle-see-other' => 303,
            'handle-moved-permanently' => 301,
            'known-content-type?' => ['valid-entity-length?', 'handle-unsupported-media-type'],
            'moved-permanently?' => ['handle-moved-permanently', 'moved-temporarily?'],
            'if-modified-since-valid-date?' => ['modified-since?', 'method-delete?'],
            'malformed?' => ['handle-malformed', 'authorized?'],
            'patch!' => 'respond-with-entity?',
            'if-unmodified-since-valid-date?' => ['unmodified-since?', 'if-none-match-exists?'],
            'handle-no-content' => 204,
            'multiple-representations?' => ['handle-multiple-representations', 'handle-ok'],
            'delete!' => 'delete-enacted?',
            'post!' => 'post-redirect?',
            'handle-malformed' => 400,
            'etag-matches-for-if-none?' => ['if-none-match?', 'if-modified-since-exists?'],
            'handle-exception' => 500,
            'respond-with-entity?' => ['multiple-representations?', 'handle-no-content'],
            'handle-conflict' => 409,
            'method-allowed?' => ['malformed?', 'handle-method-not-allowed'],
            'uri-too-long?' => ['handle-uri-too-long', 'method-allowed?'],
            'if-unmodified-since-exists?' => ['if-unmodified-since-valid-date?', 'if-none-match-exists?'],
            'post-to-gone?' => ['can-post-to-gone?', 'handle-gone'],
            'handle-method-not-allowed' => 405,
            'accept-charset-exists?' => ['charset-available?', 'accept-encoding-exists?'],
            'encoding-available?' => ['processable?', 'handle-not-acceptable'],
            'authorized?' => ['allowed?', 'handle-unauthorized'],
            'accept-exists?' => ['media-type-available?', 'accept-language-exists?'],
            'if-none-match?' => ['handle-not-modified', 'handle-precondition-failed'],
            'new?' => ['handle-created', 'respond-with-entity?'],
            'handle-service-not-available' => 503,
        ];
    }

    private function isAction($node) {
        return is_string($node) && substr($node, -1) == '!';
    }

    private function isDecision($node) {
        return is_string($node) && substr($node, -1) == '?';
    }

    private function isHandler($node) {
        return is_string($node) && substr($node, 0, 7) == 'handle-';
    }

    private function dispatch(Dance $resource, Song $context, $init = 'service-available?') {
        $node = $init;
        while (!$this->isHandler($node)) {
            if ($this->isDecision($node)) {
                list($pass, $fail) = $this->graph[$node];
                $result = $resource($node, $context);
                $node = $result ? $pass : $fail;
            } else if ($this->isAction($node)) {
                $resource($node, $context);
                $node = $this->graph[$node];
            } else {
                throw new \Exception();
            }
        }
        return [$node, $this->graph[$node]];
    }

    private function toResponse($handlerResult, $status, Song $context) {
        //TODO representation!
        return Response::create(
            var_export($handlerResult, true) . "\r\n",
            $status,
            ['content-type' => 'application/php']
        );
    }

    private function runHandler($name, $status, Song $context) {
        if (isset($context[$name])) {
            $handler = $context[$name];
            if (!is_callable($handler)) {
                throw new \Exception();
            }
            $result = call_user_func($handler, $context);
            return $this->toResponse($result, $status, $context);

        } else {
            return Response::create('', $status);
        }
    }

    /**
     * @param \Dancery\Dance $resource
     * @param Request $request
     * @return Response
     */
    function perform(Dance $resource, Request $request = null) {
        $context = new Song($request ?: Request::createFromGlobals(), $resource->conf);
        list($handler, $status) = $this->dispatch($resource, $context);
        return $this->runHandler($handler, $status, $context);
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
