<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Favorite;
use App\Services\SpoonacularService;

class CatalogueController extends Controller
{
    private const VALID_CUISINES = [
        'italian', 'mexican', 'asian', 'american', 'mediterranean',
        'french', 'thai', 'spanish',
    ];

    private const VALID_TYPES = [
        'breakfast', 'lunch', 'dinner', 'dessert', 'snack',
        'soup', 'salad', 'main course', 'appetizer',
    ];

    private const VALID_DIETS = [
        'keto', 'vegan', 'vegetarian', 'gluten-free', 'paleo',
        'primal', 'whole30', 'lacto-vegetarian', 'ovo-vegetarian', 'pescatarian',
    ];

    private const VALID_INTOLERANCES = [
        'dairy', 'egg', 'gluten', 'grain', 'peanut', 'seafood',
        'sesame', 'shellfish', 'soy', 'sulfite', 'tree nut', 'wheat',
    ];

    public function index(): void
    {
        $query = Validator::string($_GET['query'] ?? null, 0, 200);
        $query = $query ?? '';

        $cuisineArr = Validator::stringArray($_GET['cuisine'] ?? [], self::VALID_CUISINES);
        $typeArr = Validator::stringArray($_GET['type'] ?? [], self::VALID_TYPES);
        $dietArr = Validator::stringArray($_GET['diet'] ?? [], self::VALID_DIETS);
        $intolerancesArr = Validator::stringArray($_GET['intolerances'] ?? [], self::VALID_INTOLERANCES);

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
