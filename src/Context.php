<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;

class Context {
    private $request;
    private $data = [];
    private $resource;
    private $representation;
    private $ifModifiedSinceDate;

    function __construct(Request $request, Resource $resource) {
        $this->request = $request;
        $this->resource = $resource->copy();
        $this->representation = [];
    }

    function setMediaType($type) {
        $this->representation['media-type'] = $type;
    }

    function getMediaType() {
        return @$this->representation['media-type'];
    }

    function setIfModifiedSinceDate(\DateTime $date) {
        $this->ifModifiedSinceDate = $date;
    }

    function getIfModifiedSinceDate() {
        return $this->ifModifiedSinceDate;
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
