<?php

namespace Examples;

use RestMachine\WebMachine;
use Silex\Application;

class SilexTodoApp {

    private $app;
    private $webmachine;
    private $db;

    function __construct($dbFile) {
        $this->db = new \PDO("sqlite:$dbFile");
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->app = new Application();
        $this->app['debug'] = true;
        $this->webmachine = new WebMachine();
    }

    function run() {
        Todo::setupDb($this->db);

        $this->app->match('/todos', function() {
            return $this->dispatch(TodoResource::collection($this->db));
        });

        $this->app->match('/todos/{id}', function($id) {
            return $this->dispatch(TodoResource::entity($this->db, intval($id)));
        });

        $this->app->run();
    }

    private function dispatch($resource) {
        return $this->webmachine->run($resource, $this->app['request']);
    }

}
