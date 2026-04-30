<?php

namespace App\Controllers;

use App\Core\Controller;

class RecipeController extends Controller
{
    public function show($params)
    {
        $id = $params['id'] ?? 0;

        \App\Core\View::render('Recipe', [
            'id' => $id,
            'recipeName' => "Receta #$id"
        ]);
    }
}
