<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SpoonacularService;
use App\Core\Session;
use App\Models\Favorite;

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

        $isFavorite = false;
        $uid = Session::userId();
        if ($uid !== null && $recipe !== null) {
            $isFavorite = (new Favorite())->existsForUser($uid, (int) $id);
        }

        \App\Core\View::render('Recipe', [
            'id'         => $id,
            'recipe'     => $recipe,
            'isFavorite' => $isFavorite,
        ]);
    }
}