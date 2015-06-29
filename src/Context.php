<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;

class Context {
    private $request;
    private $resource;
    private $data = [];

    private $mediaType;
    private $ifModifiedSinceDate;
    private $ifUnmodifiedSinceDate;
    private $etag;

    function __construct(Request $request, Resource $resource) {
        $this->request = $request;
        $this->resource = $resource->copy();
    }

    function setMediaType($type) {
        $this->mediaType = $type;
    }

    function getMediaType() {
        return $this->mediaType;
    }

    function setIfModifiedSinceDate(\DateTime $date) {
        $this->ifModifiedSinceDate = $date;
    }

    function getIfModifiedSinceDate() {
        return $this->ifModifiedSinceDate;
    }

    public function getIfUnmodifiedSinceDate() {
        return $this->ifUnmodifiedSinceDate;
    }

    public function setIfUnmodifiedSinceDate(\DateTime $ifUnmodifiedSinceDate) {
        $this->ifUnmodifiedSinceDate = $ifUnmodifiedSinceDate;
    }

    function getEtag() {
        return $this->etag;
    }

    function setEtag($etag) {
        $this->etag = $etag;
    }

    function value($key, $default = null) {
        return $this->resource->value($key, $this, $default);
    }

    function has($key) {
        return $this->resource->has($key);
    }

    /**
     * @return Request
     */
    function getRequest() {
        return $this->request;
    }

    function __set($key, $val) {
        $this->data[$key] = $val;
    }

    function __get($key) {
        return @$this->data[$key];
    }
}
