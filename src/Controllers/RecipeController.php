<?php

namespace App\Controllers;

use App\Core\Controller;

class RecipeController extends Controller
{
    public function show($params)
    {
        $id = $params['id'] ?? 0;

        $this->view('Recipe', [
            'title' => "What2Cook - Receta #$id",
            'styles' => ['receta'],
            'recipeName' => "Receta #$id"
        ]);
    }
}
