<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Favorite;
use App\Services\SpoonacularService;

class CatalogController extends Controller
{
    public function index(): void
    {
        $query = trim((string) ($_GET['query'] ?? ''));
        $cuisine = trim((string) ($_GET['cuisine'] ?? ''));
        $type = trim((string) ($_GET['type'] ?? ''));
        $diet = $_GET['diet'] ?? '';
        if (is_array($diet)) {
            $diet = trim((string) ($diet[0] ?? ''));
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'number' => $perPage,
            'offset' => $offset,
            'addRecipeInformation' => 'true',
            'addRecipeNutrition' => 'true',
        ];

        if ($query !== '') {
            $filters['query'] = $query;
        }
        if ($cuisine !== '') {
            $filters['cuisine'] = $cuisine;
        }
        if ($type !== '') {
            $filters['type'] = $type;
        }
        if ($diet !== '') {
            $filters['diet'] = $diet;
        }

        $recipes = [];
        $totalResults = 0;
        $errorMessage = null;

        try {
            $search = (new SpoonacularService())->searchRecipes($filters);
            $recipes = $search['results'] ?? [];
            $totalResults = (int) ($search['totalResults'] ?? count($recipes));
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
        }

        $totalPages = max(1, (int) ceil($totalResults / $perPage));
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        $favoriteIds = [];
        $userId = Session::userId();
        if ($userId !== null) {
            $favoriteRows = (new Favorite())->findAllByUser($userId);
            $favoriteIds = array_map(static fn(array $favorite): int => (int) $favorite['spoonacular_id'], $favoriteRows);
        }

        View::render('Catalogue', [
            'query' => $query,
            'cuisine' => $cuisine,
            'type' => $type,
            'diet' => $diet,
            'page' => $page,
            'perPage' => $perPage,
            'totalResults' => $totalResults,
            'totalPages' => $totalPages,
            'recipes' => $recipes,
            'favoriteIds' => $favoriteIds,
            'errorMessage' => $errorMessage,
        ]);
    }
}
