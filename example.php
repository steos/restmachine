<?php

require_once 'vendor/autoload.php';

use RestMachine\Application;
use RestMachine\Resource;
use RestMachine\Context;

$defaults = Resource::create()
    ->availableMediaTypes('application/json');

$fooCollection = Resource::create($defaults)
    ->allowedMethods('GET', 'POST')
    ->get(function(Context $context) {
        return ['hi there'];
    })
    ->post(function(Context $context) {
        return ['foo' => 'the response'];
    });

$fooEntity = Resource::create($defaults)
    ->allowedMethods('GET', 'PUT')
    ->get(function(Context $context) {
        return ['return the entity'];
    })
    ->put(function(Context $context) {
        return ['update the entity'];
    });

Application::serve([
    '/foo' => [
        'name' => 'foo',
        'resource' => $fooCollection,
        'routes' => [ '/:id' => [ 'name' => 'entity',
            'resource' => $fooEntity]]]
]);
