<?php

/**
 * Entry point of the application
 */

// Autoloading y configuración inicial
require_once __DIR__ . '/../src/bootstrap.php';

// Obtener el router y despachar la petición
$router = require_once __DIR__ . '/../src/routes.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);
