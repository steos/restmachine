<?php

namespace RestMachine;

/**
 * Resource definition builder.
 *
 * @method \RestMachine\Resource handleOk(mixed $value)
 * @method \RestMachine\Resource handleCreated(mixed $value)
 *
 * @method \RestMachine\Resource post(mixed $value)
 * @method \RestMachine\Resource put(mixed $value)
 * @method \RestMachine\Resource patch(mixed $value)
 * @method \RestMachine\Resource delete(mixed $value)
 *
 * @method \RestMachine\Resource allowedMethods(mixed $value)
 * @method \RestMachine\Resource availableMediaTypes(mixed $value)
 * @method \RestMachine\Resource lastModified(mixed $value)
 *
 * @method \RestMachine\Resource isMalformed(mixed $value)
 * @method \RestMachine\Resource isProcessable(mixed $value)
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
            'can-post-to-missing?' => true,
            'can-put-to-missing?' => true,
            'delete-enacted?' => true,
            'known-content-type?' => true,

            'method-put?' => self::methodEquals('PUT'),
            'method-delete?' => self::methodEquals('DELETE'),
            'method-patch?' => self::methodEquals('PATCH'),

            'post-to-existing?' => self::methodEquals('POST'),
            'put-to-existing?' => self::methodEquals('PUT'),

            'if-modified-since-exists?' => self::hasHeader('If-Modified-Since'),

            'if-modified-since-valid-date?' => function(Context $context) {
                // TODO handle RFC850/1036 and ANSI C's asctime() format as per rfc 2616
                // http://tools.ietf.org/html/rfc2616#section-3.3
                // quote: "clients and servers that parse the date value MUST accept all three formats"
                $date = \DateTime::createFromFormat(\DateTime::RFC1123,
                    $context->getRequest()->headers->get('If-Modified-Since'));
                if ($date) {
                    $context->setIfModifiedSinceDate($date);
                }
                return $date != false;
            },

            'modified-since?' => function(Context $context) {
                if (isset($context['last-modified'])) {
                    $ifModifiedSince = $context->getIfModifiedSinceDate();
                    $lastModified = self::value($context['last-modified'], $context);
                    if (!($lastModified instanceof \DateTime)) {
                        throw new \Exception('lastModified must result in a DateTime instance');
                    }
                    return $lastModified > $ifModifiedSince;
                }
                return false;
            },

            'known-method?' => function(Context $context) {
                $methods = ['GET', 'PUT', 'POST', 'HEAD', 'DELETE'];
                return in_array($context->getRequest()->getMethod(), $methods);
            },
            'method-allowed?' => function(Context $context) {
                return in_array($context->getRequest()->getMethod(), $context['allowed-methods']);
            },

            'accept-exists?' => function(Context $context) {
                if ($context->getRequest()->headers->has('accept')) {
                    return true;
                }
                // fall back to content negotiation using */* as accept header
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
            },

        ];
        $this->conf = array_merge($builtin, $defaults);
    }

    static private function methodEquals($method) {
        return function(Context $context) use ($method) {
            return $context->getRequest()->getMethod() == $method;
        };
    }

    static private function hasHeader($header) {
        return function(Context $context) use ($header) {
            return $context->getRequest()->headers->has($header);
        };
    }

    static private function value($value, Context $context) {
        return is_callable($value)
            ? call_user_func($value, $context)
            : $value;
    }

    function __invoke($key, $context, $default = null) {
        if (!array_key_exists($key, $this->conf)) {
            return $default;
        }
        $value = $this->conf[$key];
        return self::value($value, $context);
    }

    public function __call($method, array $args) {
        if (count($args) != 1) throw new \InvalidArgumentException();
        $this->conf[$this->keyOf($method)] = $args[0];
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
