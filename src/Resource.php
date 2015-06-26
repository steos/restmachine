<?php

namespace RestMachine;

use RestMachine\Negotiate;

/**
 * Resource definition builder.
 *
 * @method \RestMachine\Resource handleOk(mixed $value)
 * @method \RestMachine\Resource post(mixed $value)
 * @method \RestMachine\Resource put(mixed $value)
 * @method \RestMachine\Resource patch(mixed $value)
 * @method \RestMachine\Resource delete(mixed $value)
 * @method \RestMachine\Resource allowedMethods(mixed $value)
 * @method \RestMachine\Resource availableMediaTypes(mixed $value)
 * @method \RestMachine\Resource isMalformed(mixed $value)
 */
class Resource {
    public $conf;

    static function create(Resource $default = null) {
        return new self($default ? $default->conf : []);
    }

    function __construct(array $defaults = []) {
        $builtin = [
            'allowed-methods' => ['GET'],
            'available-media-types' => ['text/html'],
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
            },
            'accept-exists?' => function(Context $context) {
                if ($context->getRequest()->headers->has('accept')) {
                    return true;
                }
                // fall back to content negotation using */* as accept header
                $type = Negotiate::bestAllowedContentType(['*/*'], $context['available-media-types']);
                $context->setMediaType($type);
                return false;
            },
            'media-type-available?' => function(Context $context) {
                $type = Negotiate::bestAllowedContentType(
                    $context->getRequest()->getAcceptableContentTypes(),
                    $context['available-media-types']
                );
                $context->setMediaType($type);
                return $type !== null;
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

    public function __call($method, array $args) {
        $this->conf[$this->keyOf($method)] = count($args) == 1 ? $args[0] : $args;
        return $this;
    }

    private function keyOf($method) {
        if (in_array($method, ['put', 'post', 'patch', 'delete'])) {
            return $method . '!';
        }
        $key = self::paramCase($method);
        if (strlen($key) > 3 && substr($key, 0, 3) == 'is-') {
            return substr($key, 3) . '?';
        }
        return $key;
    }

    static function paramCase($str) {
        return implode('-', array_map('strtolower', self::splitWhen($str, 'ctype_upper')));
    }

    static function splitWhen($str, callable $pred) {
        $xs = [];
        $offset = 0;
        for ($i = 0, $len = strlen($str); $i < $len; ++$i) {
            if (call_user_func($pred, $str[$i])) {
                $xs[] = substr($str, $offset, $i - $offset);
                $offset = $i;
            }
        }
        if ($offset < $len - 1) {
            $xs[] = substr($str, $offset);
        }
        return $xs;
    }
}
