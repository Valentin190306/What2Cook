<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SpoonacularService;
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
        $recipe = $this->fromLocalDb($recipeId);
        $needsTranslation = false;

        if ($recipe === null) {
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

        \App\Core\View::render('Recipe', [
            'id'         => (string) $recipeId,
            'recipe'     => $recipe,
            'isFavorite' => $isFavorite,
        ]);

        if ($needsTranslation) {
            DeferredTranslator::afterResponse(fn() => DeferredTranslator::translate($recipeId));
        }
    }

    /**
     * Busca una receta en la BD local.
     * Devuelve raw_response_es (traducido), raw_response_en (inglés fallback) o null.
     */
    /**
     * Valida que los datos decodificados tengan la estructura completa de Spoonacular.
     * Los resultados de complexSearch (catálogo) NO incluyen extendedIngredients.
     */
    private function isCompleteRecipe(?array $data): bool
    {
        return $data !== null && isset($data['extendedIngredients']);
    }

    public function fromLocalDb(int $spoonacularId): ?array
    {
        $enabled = ($_ENV['RECIPES_TABLE_ENABLED'] ?? 'true') === 'true';
        if (!$enabled) return null;

        try {
            $row = (new RecipeTranslation())->findBySpoonacularId($spoonacularId);
            if ($row) {
                $originalEn = $row['raw_response_en'] ? json_decode($row['raw_response_en'], true) : null;

                if (!empty($row['raw_response_es'])) {
                    $decoded = json_decode($row['raw_response_es'], true);
                    if (is_array($decoded) && $this->isCompleteRecipe($decoded)) {
                        return $this->normalizeRecipe($decoded, $originalEn);
                    }
                }
                if ($originalEn !== null && $this->isCompleteRecipe($originalEn)) {
                    return $this->normalizeRecipe($originalEn);
                }
            }
        } catch (\Throwable $e) {
            $this->log('error', 'Error consultando recipe_translations', ['error' => $e->getMessage()]);
        }

        return null;
    }
}