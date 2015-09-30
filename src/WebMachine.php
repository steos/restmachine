<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The actual webmachine logic.
 */
class WebMachine {
    private $serializers;
    private $graph;
    private $trace;
    private $enableTrace;

    function __construct() {
        $this->trace = [];
        $this->enableTrace = false;
        $this->serializers = [
            'application/json' => 'json_encode',
            'text/plain' => 'strval',
            'text/php' => function($val) {
                return var_export($val, true);
            },
            'application/php' => 'serialize'
        ];

        $this->graph = require __DIR__ . '/../resources/decision-graph.php';
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

    private function dispatch(Context $context, $init = 'service-available?') {
        $trace = [];
        $node = $init;
        while (!$this->isHandler($node)) {
            $traceNode = ['node' => $node];
            if ($this->isDecision($node)) {
                list($pass, $fail) = $this->graph[$node];
                $result = $context->value($node);
                $traceNode['result'] = $result;
                $node = $result ? $pass : $fail;
            } else if ($this->isAction($node)) {
                $context->value($node);
                $node = $this->graph[$node];
            } else {
                throw new \Exception("node '$node' is unknown");
            }
            $trace[] = $traceNode;
        }
        $trace[] = ['node' => $node];
        $this->trace = $trace;
        return [$node, $this->graph[$node]];
    }

    private function toResponse($handler, $status, Context $context) {
        $result = $context->value($handler, '');
        $mediaType = $context->getMediaType();
        $lastModified = $context->value('last-modified');
        $response = Response::create('', $status);
        if (!$context->getRequest()->isMethod('HEAD')) {
            $response->setContent(is_string($result)
                ? $result : $this->serialize($result, $mediaType));
        }
        Utils::setHeadersMaybe($response, [
            'Location' => $context->getLocation(),
            'Vary' => $this->buildVaryHeader($context),
            'Content-Type' => $mediaType,
            'Last-Modified' => $lastModified ? Utils::httpDate($lastModified) : null,
            'ETag' => $context->value('etag')
        ]);
        if ($this->enableTrace) {
            $this->setTraceHeaders($response);
        }
        return $response;
    }

    private function buildVaryHeader(Context $context) {
        $vary = [
            'Content-Type' => $context->getMediaType()
        ];
        return implode(',', array_keys(array_filter($vary)));
    }

    private function setTraceHeaders($response) {
        $response->headers->set('X-RestMachine-Trace',
            array_map(function($trace) {
                return array_key_exists('result', $trace)
                    ? sprintf('%-30s -> %s', $trace['node'], json_encode($trace['result']))
                    : $trace['node'];
            }, $this->trace));
    }

    /**
     * @param \RestMachine\Resource $resource
     * @param Request $request
     * @return Response
     */
    function run(Resource $resource, Request $request = null) {
        $context = new Context($request ?: Request::createFromGlobals(), $resource);
        if ($request->headers->has('X-RestMachine-Trace')) {
            $this->enableTrace();
        }
        list($handler, $status) = $this->dispatch($context);
        return $this->toResponse($handler, $status, $context);
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
        return $this;
    }

    function enableTrace($enable = true) {
        $this->enableTrace = $enable;
        return $this;
    }

    function getTrace() {
        return $this->trace;
    }
}
