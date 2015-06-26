<?php

namespace RestMachine;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebMachineTestCase extends \PHPUnit_Framework_TestCase {
    /** @var WebMachine */
    private $webMachine;

    function setUp() {
        $this->webMachine = new WebMachine();
    }

    function dispatch(Resource $resource, Request $request) {
        return $this->webMachine->run($resource, $request);
    }

    function request($method = 'GET', $content = '') {
        return Request::create('http://example.com', $method, [], [], [], [], $content);
    }

    function assertStatusCode($expect, Response $response) {
        $this->assertEquals($expect, $response->getStatusCode());
    }
}
