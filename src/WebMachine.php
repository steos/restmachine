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

    function __construct() {
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

    private function dispatch(Resource $resource, Context $context, $init = 'service-available?') {
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

    private function toResponse($handlerResult, $status, Context $context) {
        //TODO representation!
        return Response::create(
            var_export($handlerResult, true) . "\r\n",
            $status,
            ['content-type' => 'application/php']
        );
    }

    private function runHandler($name, $status, Context $context) {
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
     * @param \RestMachine\Resource $resource
     * @param Request $request
     * @return Response
     */
    function run(Resource $resource, Request $request = null) {
        $context = new Context($request ?: Request::createFromGlobals(), $resource->conf);
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
