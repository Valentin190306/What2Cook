<?php

use App\Core\Router;

$router = new Router($logger);

// Definición de rutas
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/about', 'AboutController@index');
$router->add('GET', '/diets', 'DietController@index');
$router->add('GET', '/asistente-cocina', 'AsistenteCocinaController@index');
$router->add('GET', '/asistente-dieta', 'AsistenteDietaController@index');
$router->add('GET', '/recetas', 'CatalogoRecetasController@index');
$router->add('GET', '/receta/{id}', 'RecetaController@show');
$router->add('GET', '/perfil', 'PerfilController@index');
$router->add('GET', '/login', 'AuthController@loginForm');
$router->add('GET', '/register', 'AuthController@registerForm');

// Rutas de API
$router->add('GET', '/api/dishes', 'DishController@all');
$router->add('GET', '/api/dishes/{id}', 'DishController@show');
$router->add('POST', '/api/login', 'AuthController@login');

return $router;
