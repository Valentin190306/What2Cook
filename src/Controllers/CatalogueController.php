<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Favorite;
use App\Services\SpoonacularService;

class CatalogueController extends Controller
{
    public function index(): void
    {
        $query = trim((string) ($_GET['query'] ?? ''));
        // support multiple selections per category (arrays or single values)
        $cuisineRaw = $_GET['cuisine'] ?? '';
        $typeRaw = $_GET['type'] ?? '';
        $dietRaw = $_GET['diet'] ?? '';
        $intolerancesRaw = $_GET['intolerances'] ?? '';

        $cuisineArr = [];
        if (is_array($cuisineRaw)) {
            $cuisineArr = array_values(array_filter(array_map('trim', $cuisineRaw), static fn($v) => $v !== ''));
        } elseif (trim((string) $cuisineRaw) !== '') {
            $cuisineArr = [trim((string) $cuisineRaw)];
        }

        $typeArr = [];
        if (is_array($typeRaw)) {
            $typeArr = array_values(array_filter(array_map('trim', $typeRaw), static fn($v) => $v !== ''));
        } elseif (trim((string) $typeRaw) !== '') {
            $typeArr = [trim((string) $typeRaw)];
        }

        $dietArr = [];
        if (is_array($dietRaw)) {
            $dietArr = array_values(array_filter(array_map('trim', $dietRaw), static fn($v) => $v !== ''));
        } elseif (trim((string) $dietRaw) !== '') {
            $dietArr = [trim((string) $dietRaw)];
        }

        $intolerancesArr = [];
        if (is_array($intolerancesRaw)) {
            $intolerancesArr = array_values(array_filter(array_map('trim', $intolerancesRaw), static fn($v) => $v !== ''));
        } elseif (trim((string) $intolerancesRaw) !== '') {
            $intolerancesArr = [trim((string) $intolerancesRaw)];
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
        if (!empty($cuisineArr)) {
            // join multiple cuisines with comma - Spoonacular accepts comma-separated lists
            $filters['cuisine'] = implode(',', $cuisineArr);
        }
        if (!empty($typeArr)) {
            $filters['type'] = implode(',', $typeArr);
        }
        if (!empty($dietArr)) {
            $filters['diet'] = implode(',', $dietArr);
        }
        if (!empty($intolerancesArr)) {
            $filters['intolerances'] = implode(',', $intolerancesArr);
        }

        $recipes = [];
        $totalResults = 0;
        $errorMessage = null;

        $this->log('info', 'Búsqueda en catálogo', [
            'query' => $query,
            // pass arrays to the view so inputs can render checked states
            'cuisine' => $cuisineArr,
            'type' => $typeArr,
            'diet' => $dietArr,
            'intolerances' => $intolerancesArr,
            'page' => $page,
        ]);

        try {
            $search = (new SpoonacularService())->searchRecipes($filters);
            $recipes = $search['results'] ?? [];
            $totalResults = (int) ($search['totalResults'] ?? count($recipes));
        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();
            $this->log('error', 'Error en búsqueda de catálogo: ' . $e->getMessage());
        }

        $this->log('info', 'Búsqueda completada', [
            'totalResults' => $totalResults,
            'returned' => count($recipes),
        ]);

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
            'cuisine' => $cuisineArr,
            'type' => $typeArr,
            'diet' => $dietArr,
            'intolerances' => $intolerancesArr,
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
