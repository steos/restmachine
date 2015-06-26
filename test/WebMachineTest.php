<?php

namespace RestMachine;


use Symfony\Component\HttpFoundation\Response;

class WebMachineTest extends WebMachineTestCase {

    static function validateJson(Context $context) {
        if ($context->getRequest()->getContent()) {
            $context->entity = json_decode($context->getRequest()->getContent());
            return json_last_error();
        }
        return false;
    }

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
            ->isMalformed([self::class, 'validateJson']);

        $response = $this->dispatch($resource, $this->request());
        $this->assertStatusCode(Response::HTTP_OK, $response);

        $this->assertStatusCode(Response::HTTP_BAD_REQUEST,
            $this->dispatch($resource, $this->request('POST', 'invalid json')));

        $this->assertStatusCode(Response::HTTP_OK,
            $this->dispatch($resource, $this->request('POST', json_encode(['foo' => 'bar']))));
    }

    function testUnprocessable() {
        $resource = Resource::create()
            ->allowedMethods('GET', 'POST')
            ->isMalformed([self::class, 'validateJson'])
            ->isProcessable(function(Context $context) {
                return !$context->entity || isset($context->entity->foo);
            });

        $this->assertStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->dispatch($resource, $this->request('POST', json_encode(['foobar' => 'baz']))));

        $this->assertStatusCode(Response::HTTP_OK,
            $this->dispatch($resource, $this->request('POST', json_encode(['foo' => 'bar']))));
    }

    function testMediaTypeNegotiation() {
        $resource = Resource::create()
            ->availableMediaTypes('application/json', 'application/php')
            ->handleOk(function(Context $context) {
                return ['foo' => 'bar'];
            });

        $data = ['foo' => 'bar'];
        $this->assertStatuscode(Response::HTTP_NOT_ACCEPTABLE,
            $this->dispatch($resource, $this->request('GET', '', ['Accept' => 'text/html'])));

        $this->assertEquals(json_encode($data),
            $this->dispatch($resource, $this->request('GET', '', ['Accept' => 'application/json']))->getContent());

        $this->assertEquals(serialize($data),
            $this->dispatch($resource, $this->request('GET', '', ['Accept' => 'application/php']))->getContent());
    }

}

