<?php

namespace Dancery;

/**
 * Resource definition builder.
 */
class Dance {
    private $conf;

    static function create(Dance $default = null) {
        return new self($default ? $default->conf : []);
    }

    function __construct(array $defaults = []) {
        $this->conf = array_merge($this->conf, [
            'allowed-methods' => [],
            'available-media-types' => [],

            'new?' => false,
            'service-available?' => true,
            'known-method?' => function(Song $context) {
                $methods = ['GET', 'PUT', 'POST', 'HEAD', 'DELETE'];
                return in_array($context->getRequest()->getMethod(), $methods);
            },
            'method-allowed?' => function(Song $context) {
                return in_array($context->getRequest()->getMethod(), $this->conf['allowed-methods']);
            }
        ]);
    }

    function __invoke($key, $context, $default = null) {
        if (!array_key_exists($key, $this->conf)) {
            return $default;
        }
        $value = $this->conf[$key];
        return is_callable($value)
            ? call_user_func($value, $context)
            : $value;
    }

    function get(callable $f) {
        $this->conf['get'] = $f;
        return $this;
    }

    function post(callable $f) {
        $this->conf['post'] = $f;
        return $this;
    }

    function put(callable $f) {
        $this->conf['put'] = $f;
        return $this;
    }

    function allowedMethods(...$methods) {
        $this->conf['allowed-methods'] = $methods;
        return $this;
    }

    function availableMediaTypes(...$types) {
        $this->conf['available-media-types'] = $types;
        return $this;
    }
}
