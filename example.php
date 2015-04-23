<?php

require_once 'vendor/autoload.php';

use Dancery\Soloist;
use Dancery\Dance;
use Dancery\Song;

$defaults = Dance::create()
    ->availableMediaTypes('application/json');

$fooCollection = Dance::create($defaults)
    ->allowedMethods('GET', 'POST')
    ->get(function(Song $context) {
        return ['hi there'];
    })
    ->post(function(Song $context) {
        return ['foo' => 'the response'];
    });

$fooEntity = Dance::create($defaults)
    ->allowedMethods('GET', 'PUT')
    ->get(function(Song $context) {
        return ['return the entity'];
    })
    ->put(function(Song $context) {
        return ['update the entity'];
    });

Soloist::dance([
    '/foo' => [
        'name' => 'foo',
        'resource' => $fooCollection,
        'routes' => [ '/:id' => [ 'name' => 'entity',
            'resource' => $fooEntity]]]
]);
