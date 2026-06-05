<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;

class KitchenHelperController extends Controller
{
    private const VALID_SORTS = ['healthiness', 'time'];
    private const MAX_INGREDIENTS = 20;

    public function index(): void
    {
        \App\Core\View::render('KitchenHelper');
    }

    public function single(): void
    {
        $this->requireJson();
        $body = $this->parseBody();

        $ingredients = Validator::stringArray($body['ingredients'] ?? [], null, self::MAX_INGREDIENTS);
        if (empty($ingredients)) {
            $this->log('warning', 'Single search sin ingredientes');
            $this->json(['error' => 'Ingredients required'], 400);
            return;
        }

        $sort = Validator::inList($body['sort'] ?? null, self::VALID_SORTS);

        $userId = Session::userId();
        $userDiet = '';
        $userIntolerances = [];
        if ($userId !== null) {
            $user = (new \App\Models\User())->find($userId);
            if ($user) {
                $userDiet = $user['preferences'] ?? '';
                if (!empty($user['allergies'])) {
                    $decoded = json_decode($user['allergies'], true);
                    if (is_array($decoded)) {
                        $userIntolerances = $decoded;
                    }
                }
            }
        }

        $this->log('info', 'Búsqueda single', ['ingredients' => $ingredients, 'sort' => $sort, 'diet' => $userDiet, 'intolerances' => $userIntolerances]);

        try {
            $service = new \App\Services\SpoonacularService($this->logger);

            if ($sort !== null) {
                $filters = [
                    'includeIngredients'   => implode(',', $ingredients),
                    'sort'                 => $sort,
                    'sortDirection'        => 'desc',
                    'addRecipeNutrition'   => 'true',
                    'fillIngredients'      => 'true',
                    'addRecipeInformation' => 'true',
                ];

                if (!empty($userDiet)) {
                    $filters['diet'] = $userDiet;
                }
                if (!empty($userIntolerances)) {
                    $filters['intolerances'] = implode(',', $userIntolerances);
                }

                $results = $service->searchRecipes($filters);
                $list = $results['results'] ?? $results;

                if (!is_array($list)) {
                    $this->log('error', 'Single search: respuesta inválida de Spoonacular');
                    $this->json(['error' => 'Error al obtener recetas.'], 502);
                    return;
                }

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
                unset($recipe);
            } else {
                $list = $service->searchByIngredients($ingredients, 12, true);
                if (!is_array($list)) {
                    $this->log('error', 'Single search: respuesta inválida de searchByIngredients');
                    $this->json(['error' => 'Error al obtener recetas.'], 502);
                    return;
                }
                $list = $this->enrichWithNutrition($list, $service);
            }

            $this->log('info', 'Single completada', ['results' => count($list)]);
            $this->json(['success' => true, 'data' => $list]);
        } catch (\Throwable $e) {
            $this->log('error', 'Error en single search: ' . $e->getMessage());
            $this->json(['error' => 'Error interno del servidor.'], 502);
        }
    }

    public function mealPrep(): void
    {
        $this->requireJson();
        $body = $this->parseBody();

        $ingredients = Validator::stringArray($body['ingredients'] ?? [], null, self::MAX_INGREDIENTS);
        if (empty($ingredients)) {
            $this->log('warning', 'Meal Prep sin ingredientes');
            $this->json(['error' => 'Ingredients required'], 400);
            return;
        }

        $count = Validator::integer($body['count'] ?? null, 2, 5) ?? 3;
        $sort = Validator::inList($body['sort'] ?? null, self::VALID_SORTS);

        $userId = Session::userId();
        $userDiet = '';
        $userIntolerances = [];
        if ($userId !== null) {
            $user = (new \App\Models\User())->find($userId);
            if ($user) {
                $userDiet = $user['preferences'] ?? '';
                if (!empty($user['allergies'])) {
                    $decoded = json_decode($user['allergies'], true);
                    if (is_array($decoded)) {
                        $userIntolerances = $decoded;
                    }
                }
            }
        }

        $this->log('info', 'Búsqueda Meal Prep', ['ingredients' => $ingredients, 'count' => $count, 'sort' => $sort, 'diet' => $userDiet, 'intolerances' => $userIntolerances]);

        try {
            $service = new \App\Services\SpoonacularService($this->logger);

            $filters = [
                'includeIngredients'   => implode(',', $ingredients),
                'number'               => $count * 5,
                'addRecipeNutrition'   => 'true',
                'addRecipeInformation' => 'true',
            ];

            if (!empty($userDiet)) {
                $filters['diet'] = $userDiet;
            }
            if (!empty($userIntolerances)) {
                $filters['intolerances'] = implode(',', $userIntolerances);
            }

            $results = $service->searchRecipes($filters);
            $pool = $results['results'] ?? $results;

            if (!is_array($pool)) {
                $this->log('error', 'Meal Prep: respuesta inválida de Spoonacular');
                $this->json(['error' => 'Error al obtener recetas.'], 502);
                return;
            }

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

            $this->log('info', 'Meal Prep completada', ['results' => count($selected)]);
            $this->json(['success' => true, 'data' => $selected]);
        } catch (\Throwable $e) {
            $this->log('error', 'Error en Meal Prep: ' . $e->getMessage());
            $this->json(['error' => 'Error interno del servidor.'], 502);
        }
    }

    public function recipeDetail(string $id): void
    {
        $recipeId = Validator::integer($id, 1);
        if ($recipeId === null) {
            $this->log('warning', 'Detalle: ID inválido', ['recipe_id' => $id]);
            $this->json(['error' => 'ID de receta inválido.'], 400);
            return;
        }

        $this->log('info', 'Solicitud de detalle', ['recipe_id' => $recipeId]);

        try {
            $service = new \App\Services\SpoonacularService($this->logger);
            $result  = $service->getRecipeInfo($recipeId, true);

            $this->log('info', 'Detalle completado', ['recipe_id' => $recipeId]);
            $this->json(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            $this->log('error', 'Error en detalle', ['recipe_id' => $recipeId, 'error' => $e->getMessage()]);
            $this->json(['error' => 'Error al obtener la receta.'], 502);
        }
    }

    private function enrichWithNutrition(array $recipes, \App\Services\SpoonacularService $service): array
    {
        if (empty($recipes)) {
            return [];
        }

        $ids = array_column($recipes, 'id');

        try {
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
                if (isset($info['extendedIngredients'])) {
                    $recipe['extendedIngredients'] = $info['extendedIngredients'];
                }

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
        unset($recipe);

        return $recipes;
    }
}
