<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Favorite;

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
        
        $this->json(['favorited' => $favorited]);
    }

    public function index(): void
    {
        $userId = $this->requireAuthWeb();
        $favorites = (new Favorite())->findAllByUser($userId);
        
        \App\Core\View::render('Favorites', [
            'favorites' => $favorites,
        ]);
    }
}
