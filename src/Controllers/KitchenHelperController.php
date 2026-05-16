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
                    'includeIngredients'   => implode(',', $ingredients),
                    'sort'                 => $sort,
                    'sortDirection'        => 'desc',
                    'addRecipeNutrition'   => 'true',
                    'fillIngredients'      => 'true',
                    'addRecipeInformation' => 'true',
                ]);
                $list = $results['results'] ?? $results;

                foreach ($list as &$recipe) {
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
                    if (!isset($recipe['usedIngredientCount']) && isset($recipe['usedIngredients'])) {
                        $recipe['usedIngredientCount'] = count($recipe['usedIngredients']);
                    }
                    if (!isset($recipe['missedIngredientCount']) && isset($recipe['missedIngredients'])) {
                        $recipe['missedIngredientCount'] = count($recipe['missedIngredients']);
                    }
                }
            } else {
                $list = $service->searchByIngredients($ingredients, 12, true);
                $list = $this->enrichWithNutrition($list, $service);
            }

            $this->json(['success' => true, 'data' => $list]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── API: Meal Prep ────────────────────────────────────────────────────────

    public function mealPrep(): void
    {
        $this->requireJson();
        $body = $this->parseBody();

        $ingredients = $body['ingredients'] ?? [];
        $count       = (int) ($body['count'] ?? 3);
        $sort        = $body['sort'] ?? null;

        if (!is_array($ingredients) || empty($ingredients)) {
            $this->json(['error' => 'Ingredients required'], 400);
            return;
        }

        $count = max(2, min(5, $count));

        try {
            $service = new \App\Services\SpoonacularService();
            $pool    = $service->searchByIngredients($ingredients, $count * 5, true);

            if ($sort === 'time') {
                usort($pool, function (array $a, array $b): int {
                    return ($a['readyInMinutes'] ?? 9999) <=> ($b['readyInMinutes'] ?? 9999);
                });
            } else {
                usort($pool, function (array $a, array $b): int {
                    $usedDiff = ($b['usedIngredientCount'] ?? 0) <=> ($a['usedIngredientCount'] ?? 0);
                    if ($usedDiff !== 0) {
                        return $usedDiff;
                    }
                    return ($a['missedIngredientCount'] ?? 0) <=> ($b['missedIngredientCount'] ?? 0);
                });
            }

            $selected = array_slice($pool, 0, $count);
            $selected = $this->enrichWithNutrition($selected, $service);

            $this->json(['success' => true, 'data' => $selected]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── API: Detalle de receta ─────────────────────────────────────────────────

    public function recipeDetail(string $id): void
    {
        try {
            $service = new \App\Services\SpoonacularService();
            $result  = $service->getRecipeInfo($id, true);

            $this->json(['success' => true, 'data' => $result]);
        } catch (\RuntimeException $e) {
            $this->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Agrega macros a cada receta de un array de resultados de findByIngredients.
     * nutritionWidget devuelve: calories (int), protein/carbs/fat (strings "15g").
     */
    private function enrichWithNutrition(array $recipes, \App\Services\SpoonacularService $service): array
    {
        if (empty($recipes)) {
            return [];
        }

        $ids = array_column($recipes, 'id');

        try {
            // Bulk request sin traducir porque solo nos interesan los números de nutrición
            $bulkInfo = $service->getRecipeInfoBulk($ids, true, false);
            $infoMap = [];
            foreach ($bulkInfo as $info) {
                $infoMap[(int)($info['id'] ?? 0)] = $info;
            }
        } catch (\RuntimeException $e) {
            $infoMap = [];
        }

        foreach ($recipes as &$recipe) {
            $id = (int)$recipe['id'];
            $info = $infoMap[$id] ?? null;

            if ($info) {
                $nutrients = $info['nutrition']['nutrients'] ?? [];
                $map = [];
                foreach ($nutrients as $n) {
                    $map[$n['name']] = (float) ($n['amount'] ?? 0);
                }

                $recipe['nutrition'] = [
                    'calories' => $map['Calories']     ?? 0.0,
                    'protein'  => $map['Protein']       ?? 0.0,
                    'carbs'    => $map['Carbohydrates'] ?? 0.0,
                    'fat'      => $map['Fat']           ?? 0.0,
                ];
            } else {
                $recipe['nutrition'] = ['calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
            }
        }

        return $recipes;
    }
}