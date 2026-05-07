<?php
declare(strict_types=1);

namespace App\Services;

class DietHelperService
{
    private SpoonacularService $spoonacular;

    private const DIET_MAP = [
        'vegana'        => 'vegan',
        'vegetariana'   => 'vegetarian',
        'keto'          => 'ketogenic',
        'paleo'         => 'paleo',
        'pescetariano'  => 'pescetarian',
        'whole30'       => 'whole30',
        'sin-gluten'    => 'gluten free',
    ];

    public function __construct()
    {
        $this->spoonacular = new SpoonacularService();
    }

    public function generatePlan(
        int    $durationDays,
        int    $targetCalories,
        int    $targetProtein,
        int    $targetCarbs,
        int    $targetFat,
        string $dietType = ''
    ): array {
        $spoonacularDiet = self::DIET_MAP[$dietType] ?? '';

        $macrosConfig = [
            'breakfast' => ['ratio' => 0.25, 'type' => 'breakfast'],
            'lunch'     => ['ratio' => 0.35, 'type' => 'main course'],
            'snack'     => ['ratio' => 0.10, 'type' => 'breakfast'],
            'dinner'    => ['ratio' => 0.30, 'type' => 'main course'],
        ];

        $pools = [];

        foreach ($macrosConfig as $mealName => $config) {
            $ratio = $config['ratio'];

            $filters = [
                'type' => $config['type'],
                'number' => max(30, $durationDays),
                'addRecipeNutrition' => 'true',
                'addRecipeInformation' => 'true',
            ];

            if ($spoonacularDiet !== '') {
                $filters['diet'] = $spoonacularDiet;
            }

            if ($targetCalories > 0) {
                $filters['minCalories'] = max(0, (int) round(($targetCalories * $ratio) * 0.7));
                $filters['maxCalories'] = (int) round(($targetCalories * $ratio) * 1.3);
            }
            if ($targetProtein > 0) {
                $filters['minProtein'] = max(0, (int) round(($targetProtein * $ratio) * 0.7));
                $filters['maxProtein'] = (int) round(($targetProtein * $ratio) * 1.3);
            }
            if ($targetCarbs > 0) {
                $filters['minCarbs'] = max(0, (int) round(($targetCarbs * $ratio) * 0.7));
                $filters['maxCarbs'] = (int) round(($targetCarbs * $ratio) * 1.3);
            }
            if ($targetFat > 0) {
                $filters['minFat'] = max(0, (int) round(($targetFat * $ratio) * 0.7));
                $filters['maxFat'] = (int) round(($targetFat * $ratio) * 1.3);
            }

            $results = $this->spoonacular->searchRecipes($filters);

            // Fallback 1: Si es muy restrictivo y no hay recetas, intentar solo con Calorías y Dieta con 50% de margen
            if (empty($results['results'])) {
                $fallbackFilters = [
                    'type' => $config['type'],
                    'number' => max(30, $durationDays),
                    'addRecipeNutrition' => 'true',
                    'addRecipeInformation' => 'true',
                ];
                if ($spoonacularDiet !== '') {
                    $fallbackFilters['diet'] = $spoonacularDiet;
                }
                if ($targetCalories > 0) {
                    $fallbackFilters['minCalories'] = max(0, (int) round(($targetCalories * $ratio) * 0.5));
                    $fallbackFilters['maxCalories'] = (int) round(($targetCalories * $ratio) * 1.5);
                }
                $results = $this->spoonacular->searchRecipes($fallbackFilters);
            }

            // Fallback 2: Si aun no hay, intentar sin restricciones de macros/calorías (solo dieta)
            if (empty($results['results'])) {
                $fallbackFilters2 = [
                    'type' => $config['type'],
                    'number' => max(30, $durationDays),
                    'addRecipeNutrition' => 'true',
                    'addRecipeInformation' => 'true',
                ];
                if ($spoonacularDiet !== '') {
                    $fallbackFilters2['diet'] = $spoonacularDiet;
                }
                $results = $this->spoonacular->searchRecipes($fallbackFilters2);
            }

            $pool = $results['results'] ?? [];
            shuffle($pool);
            $pools[$mealName] = $pool;
        }

        $days = [];

        for ($i = 0; $i < $durationDays; $i++) {
            $meals = [];
            $dailyIds = [];

            foreach (['breakfast', 'lunch', 'snack', 'dinner'] as $mealName) {
                $pool = $pools[$mealName];
                $recipe = null;

                if (count($pool) > 0) {
                    $startIndex = $i % count($pool);
                    // Buscar una receta que no se haya usado hoy
                    for ($j = 0; $j < count($pool); $j++) {
                        $idx = ($startIndex + $j) % count($pool);
                        $candidate = $pool[$idx];
                        if (!in_array((int)$candidate['id'], $dailyIds, true)) {
                            $recipe = $candidate;
                            break;
                        }
                    }
                    // Si todas se usaron hoy, usar la de startIndex
                    if ($recipe === null) {
                        $recipe = $pool[$startIndex];
                    }
                }

                if ($recipe !== null) {
                    $dailyIds[] = (int) $recipe['id'];
                    $nutrition = $this->extractNutritionFromRecipe($recipe);

                    $meals[] = [
                        'meal_type'        => $mealName,
                        'spoonacular_id'   => (int) $recipe['id'],
                        'title'            => $recipe['title'] ?? '',
                        'image'            => $recipe['image'] ?? '',
                        'ready_in_minutes' => (int) ($recipe['readyInMinutes'] ?? 0),
                        'servings'         => (int) ($recipe['servings'] ?? 1),
                        'calories'         => $nutrition['calories'],
                        'protein'          => $nutrition['protein'],
                        'carbs'            => $nutrition['carbs'],
                        'fat'              => $nutrition['fat'],
                    ];
                } else {
                    $meals[] = $this->emptyMeal($mealName);
                }
            }

            $totals = $this->calculateDayTotals($meals);

            $days[] = [
                'day_index'      => $i + 1,
                'total_calories' => $totals['calories'],
                'total_protein'  => $totals['protein'],
                'total_carbs'    => $totals['carbs'],
                'total_fat'      => $totals['fat'],
                'meals'          => $meals,
            ];
        }

        return [
            'meta' => [
                'duration_days'   => $durationDays,
                'diet_type'       => $dietType,
                'target_calories' => $targetCalories,
                'target_protein'  => $targetProtein,
                'target_carbs'    => $targetCarbs,
                'target_fat'      => $targetFat,
            ],
            'days' => $days,
        ];
    }

    private function extractNutritionFromRecipe(array $recipe): array
    {
        $nutrients = $recipe['nutrition']['nutrients'] ?? [];
        $map       = [];

        foreach ($nutrients as $n) {
            $map[$n['name']] = (float) ($n['amount'] ?? 0);
        }

        return [
            'calories' => $map['Calories']      ?? 0.0,
            'protein'  => $map['Protein']        ?? 0.0,
            'carbs'    => $map['Carbohydrates']  ?? 0.0,
            'fat'      => $map['Fat']            ?? 0.0,
        ];
    }

    private function calculateDayTotals(array $meals): array
    {
        $totals = ['calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];

        foreach ($meals as $meal) {
            $totals['calories'] += $meal['calories'];
            $totals['protein']  += $meal['protein'];
            $totals['carbs']    += $meal['carbs'];
            $totals['fat']      += $meal['fat'];
        }

        return $totals;
    }

    private function emptyMeal(string $mealType): array
    {
        return [
            'meal_type'        => $mealType,
            'spoonacular_id'   => 0,
            'title'            => 'Receta no encontrada (Ajuste muy restrictivo)',
            'image'            => '/assets/img/placeholder.jpg',
            'ready_in_minutes' => 0,
            'servings'         => 0,
            'calories'         => 0.0,
            'protein'          => 0.0,
            'carbs'            => 0.0,
            'fat'              => 0.0,
        ];
    }
}