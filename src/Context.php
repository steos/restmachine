<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;

class Context implements \ArrayAccess {
    private $request;
    private $data = [];
    private $resource;
    private $representation;
    private $ifModifiedSinceDate;

    function __construct(Request $request, array $resource) {
        $this->request = $request;
        $this->resource = $resource;
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

    /**
     * @return \DateTime|null
     */
    function getLastModified() {
        // TODO this shouldn't be here; refactor!
        $date = Resource::value($this['last-modified'], $this);
        if (!($date instanceof \DateTime)) {
            return null;
        }
        return $date;
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

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->resource);
    }

    public function offsetGet($offset) {
        return @$this->resource[$offset];
    }

    public function offsetSet($offset, $value) {
        throw new \Exception('you cannot modify the resource');
    }

    public function offsetUnset($offset) {
        throw new \Exception('you cannot modify the resource');
    }
}
