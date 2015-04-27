<?php

namespace RestMachine;

/**
 * Resource definition builder.
 */
class Resource {
    public $conf;

    static function create(Resource $default = null) {
        return new self($default ? $default->conf : []);
    }

    function __construct(array $defaults = []) {
        $builtin = [
            'allowed-methods' => ['GET'],
            'available-media-types' => [],
            'available-languages' => ['*'],
            'available-charsets' => ['UTF-8'],
            'available-encodings' => ['identity'],

            'new?' => true,
            'service-available?' => true,
            'authorized?' => true,
            'allowed?' => true,
            'valid-content-header?' => true,
            'valid-entity-length?' => true,
            'processable?' => true,
            'exists?' => true,
            'can-put-to-missing?' => true,
            'delete-enacted?' => true,
            'known-content-type?' => true,

            'known-method?' => function(Context $context) {
                $methods = ['GET', 'PUT', 'POST', 'HEAD', 'DELETE'];
                return in_array($context->getRequest()->getMethod(), $methods);
            },
            'method-allowed?' => function(Context $context) {
                return in_array($context->getRequest()->getMethod(), $context['allowed-methods']);
            },
            'method-put?' => function(Context $context) {
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
