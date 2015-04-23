<?php

namespace Dancery;

use Symfony\Component\HttpFoundation\Request;

class Song {
    private $request;
    private $data = [];
    function __construct(Request $request) {
        $this->request = $request;
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
