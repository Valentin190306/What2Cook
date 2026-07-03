<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Favorite;
use App\Models\RecipeTranslation;
use App\Services\SpoonacularService;
use App\Services\UnitConversionService;
use App\Services\UnitPreferenceService;

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
            $service = new SpoonacularService($this->logger);
            $search = $service->searchRecipes($filters, true);
            $recipes = $search['results'] ?? [];
            $totalResults = (int) ($search['totalResults'] ?? count($recipes));

            $model = new RecipeTranslation();
            foreach ($recipes as &$recipe) {
                $sid = (int) ($recipe['id'] ?? 0);
                if ($sid === 0) continue;
                $row = $model->findBySpoonacularId($sid);
                if ($row && !empty($row['raw_response_es'])) {
                    $decoded = json_decode($row['raw_response_es'], true);
                    if (is_array($decoded)) {
                        $recipe['title'] = $row['title_es'] ?: $decoded['title'] ?? $recipe['title'];
                        $recipe['summary'] = $decoded['summary'] ?? $recipe['summary'];
                    }
                }
            }
            unset($recipe);
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

        $convService = new UnitConversionService();
        $prefService = new UnitPreferenceService();
        $unitSystem = $prefService->getPreferredSystem($userId);
        foreach ($recipes as &$recipe) {
            $nutrition = $recipe['nutrition']['nutrients'] ?? [];
            $nutriMap = [];
            foreach ($nutrition as $n) {
                $nutriMap[$n['name']] = $n;
            }
            if (!empty($nutriMap)) {
                foreach (['Protein', 'Carbohydrates', 'Fat'] as $key) {
                    if (isset($nutriMap[$key])) {
                        $converted = $convService->convertToSystem((float) $nutriMap[$key]['amount'], 'g', $unitSystem);
                        $nutriMap[$key]['amount'] = $converted['amount'];
                        $nutriMap[$key]['unit'] = $converted['unit'];
                    }
                }
                // Rebuild nutrients array
                $recipe['nutrition']['nutrients'] = array_values($nutriMap);
            }
        }
        unset($recipe);

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
