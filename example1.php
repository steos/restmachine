<?php

require_once 'vendor/autoload.php';

use RestMachine\Resource;
use RestMachine\Context;
use RestMachine\WebMachine;

$defaults = Resource::create()
    ->availableMediaTypes(['application/json', 'application/php']);

$resource = Resource::create($defaults)
    ->allowedMethods(['GET', 'POST'])
    ->isMalformed(function(Context $context) {
        if ($context->getRequest()->getContent()) {
            json_decode($context->getRequest()->getContent());
            return json_last_error();
        }
        return false;
    })
    ->handleOk(function(Context $context) {
        return ['hi there'];
    })
    ->post(function(Context $context) {
        $context->newEntity = ['foo'];
    })
    ->handleCreated(function(Context $context) {
        return $context->newEntity;
    });

$request = PHP_SAPI != 'cli'
    ? \Symfony\Component\HttpFoundation\Request::createFromGlobals()
    : \Symfony\Component\HttpFoundation\Request::create('http://example.com/foo');

$webMachine = new WebMachine();
//$webMachine->enableTrace();
$response = $webMachine->run($resource, $request);

if (PHP_SAPI != 'cli') {
    $response->send();
} else {
    echo $response, PHP_EOL;
}
