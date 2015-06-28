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
            ->allowedMethods(['GET', 'POST'])
            ->isMalformed([self::class, 'validateJson']);

        $response = $this->dispatch($resource, $this->request());
        $this->assertStatusCode(Response::HTTP_OK, $response);

        $this->assertStatusCode(Response::HTTP_BAD_REQUEST,
            $this->dispatch($resource, $this->request('POST', 'invalid json')));

        $this->assertStatusCode(Response::HTTP_CREATED,
            $this->dispatch($resource, $this->request('POST', json_encode(['foo' => 'bar']))));
    }

    function testUnprocessable() {
        $resource = Resource::create()
            ->allowedMethods(['GET', 'POST'])
            ->isMalformed([self::class, 'validateJson'])
            ->isProcessable(function(Context $context) {
                return !$context->entity || isset($context->entity->foo);
            });

        $this->assertStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY,
            $this->dispatch($resource, $this->request('POST', json_encode(['foobar' => 'baz']))));

        $this->assertStatusCode(Response::HTTP_CREATED,
            $this->dispatch($resource, $this->request('POST', json_encode(['foo' => 'bar']))));
    }

    function testMediaTypeNegotiation() {
        $resource = Resource::create()
            ->availableMediaTypes(['application/json', 'application/php'])
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

    function testMediaTypeNegotiationWithQualityFactor() {
        $resource = Resource::create()
            ->availableMediaTypes(['text/plain', 'text/html'])
            ->handleOk(function(Context $context) {
                $type = $context->getMediaType();
                $message = "Hello World!\nHow are you doing?";
                return $type == 'text/html' ? nl2br($message) : $message;
            });
        $this->assertEquals("Hello World!\nHow are you doing?",
            $this->dispatch($resource, $this->request('GET', '', ['Accept' => 'text/html; q=0.9, text/plain']))
                ->getContent());

        $this->assertEquals("Hello World!<br />\nHow are you doing?",
            $this->dispatch($resource, $this->request('GET', '', ['Accept' => 'text/plain; q=0.8, text/html']))
                ->getContent());
    }

    function testSimpleJsonPost() {
        $resource = Resource::create()
            ->availableMediaTypes(['application/json'])
            ->allowedMethods(['POST'])
            ->isMalformed([self::class, 'validateJson'])
            ->post(function(Context $context) {
                $context->newEntity = clone $context->entity;
                $context->newEntity->id = 42;
            })
            ->handleCreated(function(Context $context) {
                return $context->newEntity;
            });

        $entity = ['name' => 'foo bar'];
        $response = $this->dispatch($resource,
            $this->request('POST', json_encode($entity)));
        $this->assertEquals(json_encode(array_merge($entity, ['id' => 42])), $response->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    function testIfModifiedSinceConditionalRequest() {
        $lastModified = new \DateTime();
        $resource = Resource::create()->lastModified($lastModified);

        $response = $this->dispatch($resource, $this->request('GET', '',
            ['If-Modified-Since' => $lastModified->format(\DateTime::RFC1123)]));
        $this->assertStatusCode(Response::HTTP_NOT_MODIFIED, $response);
        $this->assertEquals($lastModified->format(\DateTime::RFC1123),
            $response->headers->get('Last-Modified'));

        $ifModSince = clone $lastModified;
        $ifModSince->modify('-1 hour');
        $response = $this->dispatch($resource, $this->request('GET', '',
            ['If-Modified-Since' => $ifModSince->format(\DateTime::RFC1123)]));
        $this->assertStatusCode(Response::HTTP_OK, $response);
        $this->assertEquals($lastModified->format(\DateTime::RFC1123),
            $response->headers->get('Last-Modified'));
    }
}

