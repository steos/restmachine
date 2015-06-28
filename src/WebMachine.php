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

    private function dispatch(Resource $resource, Context $context, $init = 'service-available?') {
        $trace = [];
        $node = $init;
        while (!$this->isHandler($node)) {
            $traceNode = ['node' => $node];
            if ($this->isDecision($node)) {
                list($pass, $fail) = $this->graph[$node];
                $result = $resource($node, $context);
                $traceNode['result'] = $result;
                $node = $result ? $pass : $fail;
            } else if ($this->isAction($node)) {
                $resource($node, $context);
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

    private function setHeaderMaybe(Response $response, $header, $value) {
        if ($value) {
            $response->headers->set($header, $value);
        }
    }

    private function toResponse($handlerResult, $status, Context $context) {
        $mediaType = $context->getMediaType();
        $lastModified = $context->getLastModified();
        $content = is_string($handlerResult)
            ? $handlerResult
            : $this->serialize($handlerResult, $mediaType);
        $response = Response::create($content, $status);
        $this->setHeaderMaybe($response, 'Content-Type', $mediaType);
        if ($lastModified) {
            $response->headers->set('Last-Modified', $lastModified->format(\DateTime::RFC1123));
        }
        if ($this->enableTrace) {
            $response->headers->set('X-RestMachine-Trace',
                array_map(function($trace) {
                    return array_key_exists('result', $trace)
                        ? sprintf('%-30s -> %s', $trace['node'], json_encode($trace['result']))
                        : $trace['node'];
                }, $this->trace));
        }
        return $response;
    }

    private function runHandler($name, $status, Context $context) {
        if (isset($context[$name])) {
            $handler = $context[$name];
            if (!is_callable($handler)) {
                throw new \Exception("handler '$name' is not callable");
            }
            $result = call_user_func($handler, $context);
            return $this->toResponse($result, $status, $context);

        } else {
            return $this->toResponse('', $status, $context);
        }
    }

    /**
     * @param \RestMachine\Resource $resource
     * @param Request $request
     * @return Response
     */
    function run(Resource $resource, Request $request = null) {
        $context = new Context($request ?: Request::createFromGlobals(), $resource->conf);
        if ($request->headers->has('X-RestMachine-Trace')) {
            $this->enableTrace();
        }
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
