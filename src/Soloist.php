<?php

namespace Dancery;

use Symfony\Component\HttpFoundation\Response;

/**
 * This is a simple wrapper for quickly creating a standalone app.
 * Useful for experimentation but not intended for production.
 */
class Soloist {
    static function dance(array $routes) {
        // route to resource based on $routes spec using symfony routes component
        $dance = null;
        if ($dance) {
            $danceMachine = new DanceMachine();
            $response = $danceMachine->perform($dance);
            $response->send();
        } else {
            Response::create('', Response::HTTP_NOT_FOUND)->send();
        }
    }
}
