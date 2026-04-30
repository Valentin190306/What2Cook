<?php

use App\Core\Router;

$router = new Router($logger);

// Definición de rutas
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/about', 'AboutController@index');
$router->add('GET', '/diets', 'DietController@index');
$router->add('GET', '/asistente-cocina', 'KitchenHelperController@index');
$router->add('GET', '/asistente-dieta', 'DietHelperController@index');
$router->add('GET', '/recetas', 'CatalogController@index');
$router->add('GET', '/receta/{id}', 'RecipeController@show');
$router->add('GET', '/perfil', 'ProfileController@index');
$router->add('GET', '/login', 'AuthController@loginForm');
$router->add('GET', '/register', 'AuthController@registerForm');

// Rutas de API
$router->add('GET', '/api/dishes', 'DishController@all');
$router->add('GET', '/api/dishes/{id}', 'DishController@show');
$router->add('POST', '/api/login', 'AuthController@login');

return $router;
