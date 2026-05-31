<?php

use App\Core\Router;

$router = new Router($logger);

// Definición de rutas
$router->add('GET', '/', 'HomeController@index');
$router->add('GET', '/about', 'AboutController@index');
$router->add('GET', '/diets', 'DietController@index');
$router->add('GET', '/asistente-cocina', 'KitchenHelperController@index');
$router->add('GET', '/asistente-dieta', 'DietHelperController@index');

// Rutas de API — Asistente de Cocina
$router->add('POST', '/api/kitchen-helper/single',      'KitchenHelperController@single');
$router->add('POST', '/api/kitchen-helper/meal-prep',   'KitchenHelperController@mealPrep');
$router->add('GET',  '/api/kitchen-helper/recipe/{id}', 'KitchenHelperController@recipeDetail');
$router->add('GET', '/recetas', 'CatalogController@index');
$router->add('GET', '/receta/{id}', 'RecipeController@show');
$router->add('GET', '/perfil', 'ProfileController@index');
$router->add('GET', '/favoritos', 'FavoriteController@index');
$router->add('GET', '/perfil/editar', 'ProfileController@editForm');
$router->add('POST', '/perfil/editar', 'ProfileController@update');
$router->add('GET', '/login', 'AuthController@loginForm');
$router->add('GET', '/register', 'AuthController@registerForm');

$router->add('POST', '/login', 'AuthController@login');
$router->add('POST', '/register', 'AuthController@register');
$router->add('POST', '/logout', 'AuthController@logout');

// Rutas de API — Dishes
$router->add('GET', '/api/dishes', 'DishController@all');
$router->add('GET', '/api/dishes/{id}', 'DishController@show');

// Rutas de API — Asistente de Dieta
$router->add('POST', '/api/diet-helper/generate',                    'DietHelperController@generate');
$router->add('POST', '/api/diet-helper/plan',                         'DietHelperController@savePlan');
$router->add('GET',  '/api/diet-helper/plan/{id}',                    'DietHelperController@getPlan');
$router->add('GET',  '/api/diet-helper/active',                       'DietHelperController@getActivePlan');
$router->add('GET',  '/api/diet-helper/shopping-list/{id}',           'DietHelperController@getShoppingList');
$router->add('PATCH', '/api/diet-helper/shopping-list/item/{id}',     'DietHelperController@toggleShoppingItem');

// Rutas de API — Favoritos
$router->add('POST', '/api/favorites', 'FavoriteController@toggle');

return $router;
