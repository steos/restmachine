<?php

namespace RestMachine;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class WebMachineTestCase extends TestCase {
    /** @var WebMachine */
    private $webMachine;

    function setUp(): void {
        $this->webMachine = new WebMachine();
    }

    function dispatch(Resource $resource, Request $request) {
        return $this->webMachine->run($resource, $request);
    }

    function request($method = 'GET', $content = '', $headers = []) {
        $request = Request::create('http://example.com', $method, [], [], [], [], $content);
        $request->headers->add($headers);
        return $request;
    }

    function GET($resource, $headers = []) {
        return $this->dispatch($resource, $this->request('GET', '', $headers));
    }

    function POST($resource, $content, $headers = []) {
        return $this->dispatch($resource, $this->request('POST', $content, $headers));
    }

    function PUT($resource, $content, $headers = []) {
        return $this->dispatch($resource, $this->request('PUT', $content, $headers));
    }

    function assertStatusCode($expect, Response $response) {
        $this->assertEquals($expect, $response->getStatusCode());
    }
}
