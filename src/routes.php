<?php

use App\Core\Router;

$router = new Router();

// Definición de rutas
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/about', 'AboutController@index');
$router->add('GET', '/diets', 'DietController@index');

// Rutas de API
$router->add('GET', '/api/dishes', 'DishController@all');
$router->add('GET', '/api/dishes/{id}', 'DishController@show');
$router->add('POST', '/api/login', 'AuthController@login');

return $router;
