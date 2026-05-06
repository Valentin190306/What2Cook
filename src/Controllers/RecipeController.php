<?php

namespace App\Controllers;

use App\Core\Controller;

class RecipeController extends Controller
{
    public function show(string $id): void
    {
        \App\Core\View::render('Recipe', [
            'id'         => $id,
            'recipeName' => "Receta #{$id}",
        ]);
    }
}