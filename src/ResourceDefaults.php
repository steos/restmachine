<?php

namespace RestMachine;

class ResourceDefaults {
    static function create() {
        return [
            'allowed-methods' => ['GET', 'HEAD'],
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

            'is-options?' => self::methodEquals('OPTIONS'),
            'method-put?' => self::methodEquals('PUT'),
            'method-delete?' => self::methodEquals('DELETE'),
            'method-patch?' => self::methodEquals('PATCH'),

            'post-to-existing?' => self::methodEquals('POST'),
            'put-to-existing?' => self::methodEquals('PUT'),
            'post-to-gone?' => self::methodEquals('POST'),

            'if-match-star-exists-for-missing?' => self::headerEquals('If-Match', '*'),
            'if-match-star?' => self::headerEquals('If-Match', '*'),
            'if-none-match-star?' => self::headerEquals('If-None-Match', '*'),

            'etag-matches-for-if-none?' => self::matchEtag('If-None-Match'),
            'etag-matches-for-if-match?' => self::matchEtag('If-Match'),

            'if-unmodified-since-exists?' => self::hasHeader('If-Unmodified-Since'),
            'if-modified-since-exists?' => self::hasHeader('If-Modified-Since'),

            'if-none-match?' => function(Context $context) {
                return in_array($context->getRequest()->getMethod(), ['GET', 'HEAD']);
            },

            'if-modified-since-valid-date?' => function(Context $context) {
                $date = Utils::parseHttpDate($context->getRequest()->headers->get('If-Modified-Since'));
                if ($date) {
                    $context->setIfModifiedSinceDate($date);
                }
                return $date != false;
            },

            'if-unmodified-since-valid-date?' => function(Context $context) {
                $date = Utils::parseHttpDate($context->getRequest()->headers->get('If-Unmodified-Since'));
                if ($date) {
                    $context->setIfUnmodifiedSinceDate($date);
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

            'unmodified-since?' => function(Context $context) {
                $lastModified = $context->value('last-modified');
                if ($lastModified) {
                    $ifUnmodifiedSince = $context->getIfUnmodifiedSinceDate();
                    return $lastModified > $ifUnmodifiedSince;
                }
                return false;
            },

            'known-method?' => function(Context $context) {
                $methods = ['GET', 'PUT', 'POST', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'PATCH'];
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

    static private function headerEquals($header, $value) {
        return function(Context $context) use ($header, $value) {
            return $value == $context->getRequest()->headers->get($header);
        };
    }

    static function matchEtag($header) {
        return function(Context $context) use ($header) {
            $context->setEtag($context->value('etag'));
            return $context->getEtag() == $context->getRequest()->headers->get($header);
        };
    }
}
