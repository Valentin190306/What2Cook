<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Favorite;
use App\Models\MealPrepFavorite;
use App\Models\RecipeTranslation;
use App\Services\RecipeStorageService;
use App\Services\Translation\DeferredTranslator;
use App\Services\UnitConversionService;
use App\Services\UnitPreferenceService;

class FavoriteController extends Controller
{
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

        $likesCount = (new Favorite())->countByRecipe($spoonacularId);

        $this->log('info', 'Toggle favorito', [
            'user_id' => $userId,
            'spoonacular_id' => $spoonacularId,
            'favorited' => $favorited,
            'likes_count' => $likesCount,
        ]);

        $this->json([
            'favorited' => $favorited,
            'likesCount' => $likesCount,
        ]);
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
        
        $convService = new UnitConversionService();
        $prefService = new UnitPreferenceService();
        $unitSystem = $prefService->getPreferredSystem($userId);
        foreach ($favorites as &$recipe) {
            $nutrition = $recipe['nutrition']['nutrients'] ?? [];
            $nutriMap = [];
            foreach ($nutrition as $n) {
                $nutriMap[$n['name']] = $n;
            }
            foreach (['Protein', 'Carbohydrates', 'Fat'] as $key) {
                if (isset($nutriMap[$key])) {
                    $converted = $convService->convertToSystem((float) $nutriMap[$key]['amount'], 'g', $unitSystem);
                    $nutriMap[$key]['amount'] = $converted['amount'];
                    $nutriMap[$key]['unit'] = $converted['unit'];
                }
            }
            $recipe['nutrition']['nutrients'] = array_values($nutriMap);
        }
        unset($recipe);
        
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

    private function resolveBulkFromDbOrApi(array $spoonacularIds, array &$needsTranslation = []): array
    {
        $storage = new RecipeStorageService($this->logger);
        return $storage->resolveBulk($spoonacularIds, $needsTranslation);
    }
}
