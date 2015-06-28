<?php

namespace RestMachine;

class ResourceDefaults {
    static function create() {
        return [
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
                $lastModified = $context->value('last-modified');
                if ($lastModified) {
                    $ifModifiedSince = $context->getIfModifiedSinceDate();
                    return $lastModified > $ifModifiedSince;
                }
                return false;
            },

            'known-method?' => function(Context $context) {
                $methods = ['GET', 'PUT', 'POST', 'HEAD', 'DELETE'];
                return in_array($context->getRequest()->getMethod(), $methods);
            },
            'method-allowed?' => function(Context $context) {
                return in_array($context->getRequest()->getMethod(),
                    $context->value('allowed-methods'));
            },

            'accept-exists?' => function(Context $context) {
                if ($context->getRequest()->headers->has('accept')) {
                    return true;
                }
                // fall back to content negotiation using */* as accept header
                $type = Negotiate::bestAllowedContentType(['*/*'],
                    $context->value('available-media-types'));
                $context->setMediaType($type);
                return false;
            },
            'media-type-available?' => function(Context $context) {
                $type = Negotiate::bestAllowedContentType(
                    $context->getRequest()->getAcceptableContentTypes(),
                    $context->value('available-media-types')
                );
                $context->setMediaType($type);
                return $type !== null;
            },

        ];
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
}
