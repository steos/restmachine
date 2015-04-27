<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;

class Context implements \ArrayAccess {
    private $request;
    private $data = [];
    private $self;
    function __construct(Request $request, array $self) {
        $this->request = $request;
        $this->self = $self;
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
        return array_key_exists($offset, $this->self);
    }

    public function offsetGet($offset) {
        return @$this->self[$offset];
    }

    public function offsetSet($offset, $value) {
        throw new \Exception();
    }

    public function offsetUnset($offset) {
        throw new \Exception();
    }
}
