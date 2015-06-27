<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;

class Context implements \ArrayAccess {
    private $request;
    private $data = [];
    private $resource;
    private $representation;

    function __construct(Request $request, array $resource) {
        $this->request = $request;
        $this->resource = $resource;
        $this->representation = [];
    }

    function setMediaType($type) {
        $this->representation['media-type'] = $type;
    }

    function getMediaType() {
        return $this->representation['media-type'];
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
