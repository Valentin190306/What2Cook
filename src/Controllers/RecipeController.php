<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SpoonacularService;

class RecipeController extends Controller
{
    public function show(string $id): void
    {
        try {
            $service = new SpoonacularService();
            $recipe  = $service->getRecipeInfo((int) $id, true);
        } catch (\RuntimeException $e) {
            $recipe = null;
        }

        \App\Core\View::render('Recipe', [
            'id'     => $id,
            'recipe' => $recipe,
        ]);
    }
}