<?php

namespace SilexTodos;

use RestMachine\WebMachine;
use Silex\Application as SilexApp;

class App {

    private $app;
    private $webmachine;
    private $db;

    function __construct($dbFile) {
        $this->db = new \PDO("sqlite:$dbFile");
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->app = new SilexApp();
        $this->app['debug'] = true;
        $this->webmachine = new WebMachine();
    }

    function run() {

        $this->db->query(
<<<SQL
CREATE TABLE IF NOT EXISTS todos (
  todo_id INTEGER PRIMARY KEY AUTOINCREMENT,
  todo_text TEXT NOT NULL,
  todo_done INTEGER NOT NULL DEFAULT 0
)
SQL
        );

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
