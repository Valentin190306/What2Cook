<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SpoonacularService;
use App\Services\RecipeStorageService;
use App\Services\Traits\NutritionNormalizer;
use App\Services\Translation\DeferredTranslator;
use App\Core\Session;
use App\Models\Favorite;
use App\Models\RecipeTranslation;

class RecipeController extends Controller
{
    use NutritionNormalizer;

    public function show(string $id): void
    {
        $this->log('info', 'Viendo receta', ['recipe_id' => $id]);

        $recipeId = (int) $id;
        $storage = new RecipeStorageService($this->logger);
        $recipe = $storage->findLocal($recipeId);
        $needsTranslation = false;

        if ($recipe !== null) {
            $row = (new RecipeTranslation())->findBySpoonacularId($recipeId);
            if ($row && empty($row['raw_response_es'])) {
                $needsTranslation = true;
            }
        } else {
            try {
                $service = new SpoonacularService($this->logger);
                $recipe  = $service->getRecipeInfo($recipeId, true, false);
                (new RecipeTranslation())->saveRaw($recipeId, $recipe);
                $recipe = $this->normalizeRecipe($recipe);
                $needsTranslation = true;
            } catch (\RuntimeException $e) {
                $this->log('error', 'Error al cargar receta', ['recipe_id' => $id, 'error' => $e->getMessage()]);
                $recipe = null;
            }
        }

        $isFavorite = false;
        $uid = Session::userId();
        if ($uid !== null && $recipe !== null) {
            $isFavorite = (new Favorite())->existsForUser($uid, $recipeId);
        }

        $likesCount = 0;
        if ($recipe !== null) {
            $likesCount = (new Favorite())->countByRecipe($recipeId);
        }

        \App\Core\View::render('Recipe', [
            'id'         => (string) $recipeId,
            'recipe'     => $recipe,
            'isFavorite' => $isFavorite,
            'likesCount' => $likesCount,
        ]);

        if ($needsTranslation) {
            DeferredTranslator::afterResponse(fn() => DeferredTranslator::translate($recipeId));
        }
    }
}