<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Favorite;
use App\Models\MealPrepFavorite;

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
        $favorites = (new Favorite())->findAllByUser($userId);
        $mealPrepFavorites = (new MealPrepFavorite())->findAllByUser($userId);
        
        \App\Core\View::render('Favorites', [
            'favorites' => $favorites,
            'mealPrepFavorites' => $mealPrepFavorites,
        ]);
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
        
        try {
            $service = new \App\Services\SpoonacularService($this->logger);
            $recipes = $service->getRecipeInfoBulk($recipeIds, true, false);
            
            foreach ($recipes as &$recipe) {
                if (isset($recipe['nutrition']['nutrients'])) {
                    $map = [];
                    foreach ($recipe['nutrition']['nutrients'] as $n) {
                        $map[$n['name']] = (float) ($n['amount'] ?? 0);
                    }
                    $recipe['nutrition'] = [
                        'calories' => $map['Calories']     ?? 0.0,
                        'protein'  => $map['Protein']       ?? 0.0,
                        'carbs'    => $map['Carbohydrates'] ?? 0.0,
                        'fat'      => $map['Fat']           ?? 0.0,
                    ];
                }
            }
            unset($recipe);
            
            $this->json([
                'success' => true,
                'ingredients' => json_decode($mp['ingredients'], true) ?? [],
                'recipe_ids' => $recipeIds,
                'servings' => json_decode($mp['servings'], true) ?? [],
                'recipes' => $recipes
            ]);
        } catch (\Throwable $e) {
            $this->log('error', 'Error al obtener recetas de meal prep favorito', [
                'user_id' => $userId,
                'meal_prep_id' => $mpId,
                'error' => $e->getMessage()
            ]);
            $this->json(['error' => 'Error al obtener las recetas del meal prep.'], 500);
        }
    }
}
