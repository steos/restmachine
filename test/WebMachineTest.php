<?php

namespace RestMachine;


use Symfony\Component\HttpFoundation\Response;

class WebMachineTest extends WebMachineTestCase {

    function testMinimalResource() {
        $resource = Resource::create();
        $response = $this->dispatch($resource, $this->request());
        $this->assertStatusCode(Response::HTTP_OK, $response);
    }

    function testAllowedMethods() {
        $resource = Resource::create()->allowedMethods(['POST']);
        $response = $this->dispatch($resource, $this->request());
        $this->assertStatusCode(Response::HTTP_METHOD_NOT_ALLOWED, $response);
    }

    function testMalformed() {
        $resource = Resource::create()
            ->allowedMethods('GET', 'POST')
            ->isMalformed(function(Context $context) {
                if ($context->getRequest()->getContent()) {
                    json_decode($context->getRequest()->getContent());
                    return json_last_error();
                }
                return false;
            });

        $response = $this->dispatch($resource, $this->request());
        $this->assertStatusCode(Response::HTTP_OK, $response);

        $this->assertStatusCode(Response::HTTP_BAD_REQUEST,
            $this->dispatch($resource, $this->request('POST', 'invalid json')));

        $this->assertStatusCode(Response::HTTP_OK,
            $this->dispatch($resource, $this->request('POST', json_encode(['foo' => 'bar']))));
    }

}

