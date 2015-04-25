<?php

namespace Dancery;

/**
 * Resource definition builder.
 */
class Dance {
    public $conf;

    static function create(Dance $default = null) {
        return new self($default ? $default->conf : []);
    }

    function __construct(array $defaults = []) {
        $builtin = [
            'allowed-methods' => [],
            'available-media-types' => [],
            'new?' => false,
            'service-available?' => true,
            'authorized?' => true,
            'allowed?' => true,
            'valid-entity-length?' => true,
            'processable?' => true,
            'exists?' => true,
            'known-method?' => function(Song $context) {
                $methods = ['GET', 'PUT', 'POST', 'HEAD', 'DELETE'];
                return in_array($context->getRequest()->getMethod(), $methods);
            },
            'method-allowed?' => function(Song $context) {
                return in_array($context->getRequest()->getMethod(), $context['allowed-methods']);
            },
            'valid-content-header?' => function(Song $context) {
                return true; //TODO validate content header!
            },
            'known-content-type?' => function(Song $context) {
                $type = $context->getRequest()->getContentType();
                return $type === null || in_array($type, $context['available-media-types']);
            },
            'method-put?' => function(Song $context) {
                return $context->getRequest()->getMethod() == 'PUT';
            }
        ];
        $this->conf = array_merge($builtin, $defaults);
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

    function handleOk(callable $f) {
        $this->conf['handle-ok'] = $f;
        return $this;
    }

    function post(callable $f) {
        $this->conf['post!'] = $f;
        return $this;
    }

    function put(callable $f) {
        $this->conf['put!'] = $f;
        return $this;
    }

    function allowedMethods() {
        $this->conf['allowed-methods'] = func_get_args();
        return $this;
    }

    function availableMediaTypes() {
        $this->conf['available-media-types'] = func_get_args();
        return $this;
    }
}
