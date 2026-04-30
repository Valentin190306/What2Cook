<?php

/**
 * Entry point of the application
 */

require_once __DIR__ . '/../src/bootstrap.php';

$router = require_once __DIR__ . '/../src/routes.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);
