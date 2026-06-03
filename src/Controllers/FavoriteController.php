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
}
