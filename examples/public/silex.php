<?php

require __DIR__ . '/../../vendor/autoload.php';

use Examples\SilexTodoApp;

$app = new SilexTodoApp(__DIR__ . '/../todos.db');
$app->run();
