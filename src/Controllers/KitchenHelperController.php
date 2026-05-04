<?php

namespace App\Controllers;

use App\Core\Controller;

class KitchenHelperController extends Controller
{
    // ── Vista ─────────────────────────────────────────────────────────────────

    public function index(): void
    {
        \App\Core\View::render('KitchenHelper');
    }

    // ── API: Búsqueda por ingredientes ────────────────────────────────────────

    /**
     * POST /api/kitchen-helper/single
     *
     * Body JSON:
     * {
     *   "ingredients": ["chicken", "rice"],
     *   "sort": "healthiness"|"time"|null
     * }
     *
     * Si se indica sort, usa complexSearch; de lo contrario findByIngredients.
     */
    public function single(): void
    {
        $this->requireJson();
        $body = $this->parseBody();

        $ingredients = $body['ingredients'] ?? [];
        $sort        = $body['sort']        ?? null;

        if (!is_array($ingredients) || empty($ingredients)) {
            $this->json(['error' => 'Ingredients required'], 400);
            return;
        }

        try {
            $service = new \App\Services\SpoonacularService();

            if ($sort === 'healthiness' || $sort === 'time') {
                $results = $service->searchRecipes([
                    'includeIngredients' => implode(',', $ingredients),
                    'sort'               => $sort,
                    'sortDirection'      => 'desc',
                ]);
            } else {
                $results = $service->searchByIngredients($ingredients, 12, true);
            }

            $this->json(['success' => true, 'data' => $results]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── API: Meal Prep ────────────────────────────────────────────────────────

    /**
     * POST /api/kitchen-helper/meal-prep
     *
     * Body JSON:
     * {
     *   "ingredients": ["chicken", "rice"],
     *   "count": 3
     * }
     *
     * Devuelve `count` recetas (2–5) optimizadas para reutilizar ingredientes.
     */
    public function mealPrep(): void
    {
        $this->requireJson();
        $body = $this->parseBody();

        $ingredients = $body['ingredients'] ?? [];
        $count       = (int) ($body['count'] ?? 3);

        if (!is_array($ingredients) || empty($ingredients)) {
            $this->json(['error' => 'Ingredients required'], 400);
            return;
        }

        // Clamp count to [2, 5]
        $count = max(2, min(5, $count));

        try {
            $service = new \App\Services\SpoonacularService();
            $pool    = $service->searchByIngredients($ingredients, $count * 5, true);

            // Sort: usedIngredientCount DESC, then missedIngredientCount ASC
            usort($pool, function (array $a, array $b): int {
                $usedDiff = ($b['usedIngredientCount'] ?? 0) <=> ($a['usedIngredientCount'] ?? 0);
                if ($usedDiff !== 0) {
                    return $usedDiff;
                }
                return ($a['missedIngredientCount'] ?? 0) <=> ($b['missedIngredientCount'] ?? 0);
            });

            $selected = array_slice($pool, 0, $count);

            $this->json(['success' => true, 'data' => $selected]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── API: Detalle de receta ─────────────────────────────────────────────────

    /**
     * GET /api/kitchen-helper/recipe/{id}
     *
     * Devuelve información completa (con nutrición) de una receta por su ID.
     */
    public function recipeDetail(int $id): void
    {
        try {
            $service = new \App\Services\SpoonacularService();
            $result  = $service->getRecipeInfo($id, true);

            $this->json(['success' => true, 'data' => $result]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }
}
