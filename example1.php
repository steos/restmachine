<?php

require_once 'vendor/autoload.php';

use RestMachine\Resource;
use RestMachine\Context;
use RestMachine\WebMachine;

$defaults = Resource::create()
    ->availableMediaTypes('application/json', 'application/php');

$foo = Resource::create($defaults)
    ->allowedMethods('GET', 'POST')
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
        return ['foo' => 'the response'];
    });

$request = PHP_SAPI != 'cli'
    ? \Symfony\Component\HttpFoundation\Request::createFromGlobals()
    : \Symfony\Component\HttpFoundation\Request::create('http://example.com/foo');

$danceMachine = new WebMachine();
$response = $danceMachine->run($foo, $request);

if (PHP_SAPI != 'cli') {
    $response->send();
} else {
    echo $response, PHP_EOL;
}
