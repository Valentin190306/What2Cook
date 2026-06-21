<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Favorite;
use App\Models\MealPrepFavorite;
use App\Models\RecipeTranslation;
use App\Services\Traits\NutritionNormalizer;
use App\Services\Translation\DeferredTranslator;

class FavoriteController extends Controller
{
    use NutritionNormalizer;
    public function toggle(): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();
        
        $body = $this->parseBody();
        $spoonacularId = (int) ($body['spoonacular_id'] ?? 0);
        
        if ($spoonacularId <= 0) {
            $this->json(['error' => 'spoonacular_id inválido.'], 422);
        }
        
        $title = trim((string) ($body['title'] ?? ''));
        $image = $body['image'] ?? null;
        if ($image !== null) {
            $image = trim((string) $image);
            if ($image === '') {
                $image = null;
            }
        }
        
        $favorited = (new Favorite())->toggle($userId, [
            'spoonacular_id' => $spoonacularId,
            'title' => $title,
            'image' => $image,
        ]);

        $this->log('info', 'Toggle favorito', [
            'user_id' => $userId,
            'spoonacular_id' => $spoonacularId,
            'favorited' => $favorited,
        ]);

        $this->json(['favorited' => $favorited]);
    }

    public function index(): void
    {
        $userId = $this->requireAuthWeb();
        $favoritesData = (new Favorite())->findAllByUser($userId);
        
        $favorites = [];
        if (!empty($favoritesData)) {
            $spoonacularIds = array_map(function($fav) {
                return (int) $fav['spoonacular_id'];
            }, $favoritesData);

            $needsTranslation = [];
            $favorites = $this->resolveBulkFromDbOrApi($spoonacularIds, $needsTranslation);
        }
        
        $mealPrepFavorites = (new MealPrepFavorite())->findAllByUser($userId);
        
        \App\Core\View::render('Favorites', [
            'favorites' => $favorites,
            'mealPrepFavorites' => $mealPrepFavorites,
        ]);

        if (isset($needsTranslation) && !empty($needsTranslation)) {
            DeferredTranslator::afterResponse(function () use ($needsTranslation) {
                foreach ($needsTranslation as $sid) {
                    DeferredTranslator::translate($sid);
                }
            });
        }
    }
    
    public function indexApi(): void
    {
        $userId = $this->requireAuthApi();
        $favorites = (new Favorite())->findAllByUser($userId);
        
        $this->json(['favorites' => $favorites]);
    }
    
    // Meal Prep Favorites
    public function toggleMealPrep(): void
    {
        $userId = $this->requireAuthApi();
        $this->requireJson();
        
        $body = $this->parseBody();
        $ingredients = $body['ingredients'] ?? [];
        $recipeIds = $body['recipe_ids'] ?? [];
        $servings = $body['servings'] ?? [];
        
        if (empty($ingredients) || empty($recipeIds)) {
            $this->json(['error' => 'Datos de meal prep inválidos.'], 422);
            return;
        }
        
        $favorited = (new MealPrepFavorite())->toggle($userId, [
            'ingredients' => $ingredients,
            'recipe_ids' => $recipeIds,
            'servings' => $servings,
        ]);

        if ($favorited) {
            $recipesDetails = $body['recipes_details'] ?? [];
            $favoriteModel = new Favorite();
            foreach ($recipesDetails as $recipeDetail) {
                $sid = (int) ($recipeDetail['spoonacular_id'] ?? 0);
                if ($sid > 0 && !$favoriteModel->existsForUser($userId, $sid)) {
                    $favoriteModel->toggle($userId, [
                        'spoonacular_id' => $sid,
                        'title' => trim((string) ($recipeDetail['title'] ?? '')),
                        'image' => $recipeDetail['image'] ?? null,
                    ]);
                }
            }
        } else {
            $favoriteModel = new Favorite();
            foreach ($recipeIds as $rid) {
                $sid = (int) $rid;
                if ($sid > 0 && $favoriteModel->existsForUser($userId, $sid)) {
                    $favoriteModel->deleteForUser($userId, $sid);
                }
            }
        }

        $this->log('info', 'Toggle favorito de meal prep', [
            'user_id' => $userId,
            'favorited' => $favorited,
        ]);

        $this->json(['favorited' => $favorited]);
    }
    
    public function mealPrepIndexApi(): void
    {
        $userId = $this->requireAuthApi();
        $favorites = (new MealPrepFavorite())->findAllByUser($userId);
        
        $this->json(['favorites' => $favorites]);
    }

    public function showMealPrepApi(string $id): void
    {
        $userId = $this->requireAuthApi();
        $mpId = (int) $id;
        
        if ($mpId <= 0) {
            $this->json(['error' => 'ID inválido.'], 400);
            return;
        }
        
        $mp = (new MealPrepFavorite())->find($mpId);
        if ($mp === null || (int)$mp['user_id'] !== $userId) {
            $this->json(['error' => 'Meal prep no encontrado.'], 404);
            return;
        }
        
        $recipeIds = json_decode($mp['recipe_ids'], true) ?? [];
        $needsTranslation = [];
        $recipes = $this->resolveBulkFromDbOrApi($recipeIds, $needsTranslation);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'ingredients' => json_decode($mp['ingredients'], true) ?? [],
            'recipe_ids' => $recipeIds,
            'servings' => json_decode($mp['servings'], true) ?? [],
            'recipes' => $recipes,
        ]);

        if (!empty($needsTranslation)) {
            DeferredTranslator::afterResponse(function () use ($needsTranslation) {
                foreach ($needsTranslation as $sid) {
                    DeferredTranslator::translate($sid);
                }
            });
        }
        exit;
    }

    private function isCompleteRecipe(?array $data): bool
    {
        return $data !== null && isset($data['extendedIngredients']);
    }

    /**
     * Para una lista de spoonacular_ids, resuelve cada receta desde la BD local
     * (recipe_translations) si existe, o desde Spoonacular API como fallback.
     *
     * @param array $spoonacularIds
     * @param array &$needsTranslation Se llena con los IDs que requieren traducción background
     * @return array
     */
    private function resolveBulkFromDbOrApi(array $spoonacularIds, array &$needsTranslation = []): array
    {
        $enabled = ($_ENV['RECIPES_TABLE_ENABLED'] ?? 'true') === 'true';
        $result = [];
        $missingIds = [];

        if ($enabled) {
            $model = new RecipeTranslation();
            foreach ($spoonacularIds as $sid) {
                $sid = (int) $sid;
                try {
                    $row = $model->findBySpoonacularId($sid);
                    if ($row) {
                        if (!empty($row['raw_response_es'])) {
                            $decoded = json_decode($row['raw_response_es'], true);
                            if (is_array($decoded) && $this->isCompleteRecipe($decoded)) {
                                $result[] = $this->normalizeRecipe($decoded);
                                continue;
                            }
                        }
                        $originalEn = $row['raw_response_en'] ? json_decode($row['raw_response_en'], true) : null;
                        if ($originalEn !== null && $this->isCompleteRecipe($originalEn)) {
                            $result[] = $this->normalizeRecipe($originalEn);
                            $needsTranslation[] = $sid;
                            continue;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log('error', 'Error consultando recipe_translations', ['id' => $sid, 'error' => $e->getMessage()]);
                }
                $missingIds[] = $sid;
            }
        } else {
            $missingIds = $spoonacularIds;
        }

        if (!empty($missingIds)) {
            try {
                $service = new \App\Services\SpoonacularService($this->logger);
                $fetched = $service->getRecipeInfoBulk($missingIds, true, false);
                foreach ($fetched as $recipe) {
                    $sid = (int) ($recipe['id'] ?? 0);
                    (new RecipeTranslation())->saveRaw($sid, $recipe);
                    $result[] = $recipe;
                    $needsTranslation[] = $sid;
                }
            } catch (\Throwable $e) {
                $this->log('error', 'Error fetching bulk recipe info', ['error' => $e->getMessage()]);

                foreach ($missingIds as $sid) {
                    $result[] = [
                        'id' => $sid,
                        'title' => '',
                        'image' => null,
                        'readyInMinutes' => null,
                        'servings' => null,
                        'diets' => [],
                        'dishTypes' => [],
                        'nutrition' => ['nutrients' => []],
                    ];
                }
            }
        }

        // Re-index by spoonacular id to preserve original order
        $ordered = [];
        $resultMap = [];
        foreach ($result as $r) {
            $resultMap[(int) ($r['id'] ?? 0)] = $r;
        }
        foreach ($spoonacularIds as $sid) {
            if (isset($resultMap[(int) $sid])) {
                $ordered[] = $resultMap[(int) $sid];
            }
        }

        // Normalize nutrition for all recipes (including API fallback data)
        foreach ($ordered as &$recipe) {
            $recipe = $this->normalizeRecipe($recipe);
        }
        unset($recipe);

        return $ordered;
    }
}
