<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Log\LoggerInterface;

class DietHelperService
{
    private SpoonacularService $spoonacular;
    private ?LoggerInterface $logger = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->spoonacular = new SpoonacularService($logger);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $module = (new \ReflectionClass($this))->getShortName();
        $this->logger->log($level, "[{$module}] {$message}", $context);
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
            srand(crc32($mealName . date('Y-m-d')));
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

                $poolSize = count($pool);

                if ($poolSize > 0) {
                    // Intentar encontrar una receta no usada en los últimos 3 días
                    $recentIds = array_slice(
                        array_column(
                            array_filter($days, fn($d) => count($d['meals']) > 0),
                            'meals'
                        ),
                        -3
                    );
                    $recentUsed = [];
                    foreach ($recentIds as $dayMeals) {
                        foreach ($dayMeals as $m) {
                            if ($m['meal_type'] === $mealName) {
                                $recentUsed[] = $m['spoonacular_id'];
                            }
                        }
                    }

                    // Buscar la primera receta del pool que no esté en $recentUsed ni en $dailyIds
                    $startIndex = $i % $poolSize;
                    for ($j = 0; $j < $poolSize; $j++) {
                        $idx = ($startIndex + $j) % $poolSize;
                        $candidate = $pool[$idx];
                        $candidateId = (int) $candidate['id'];
                        if (
                            !in_array($candidateId, $dailyIds, true) &&
                            !in_array($candidateId, $recentUsed, true)
                        ) {
                            $recipe = $candidate;
                            break;
                        }
                    }

                    // Fallback: si todas las recetas fueron usadas recientemente, usar cualquiera no usada hoy
                    if ($recipe === null) {
                        for ($j = 0; $j < $poolSize; $j++) {
                            $idx = ($startIndex + $j) % $poolSize;
                            $candidate = $pool[$idx];
                            if (!in_array((int)$candidate['id'], $dailyIds, true)) {
                                $recipe = $candidate;
                                break;
                            }
                        }
                    }

                    // Último fallback: usar startIndex sin restricciones
                    if ($recipe === null) {
                        $recipe = $pool[$startIndex];
                    }
                }

                if ($recipe !== null) {
                    $dailyIds[] = (int) $recipe['id'];
                    $nutrition = $this->extractNutritionFromRecipe($recipe);

                    $multiplier = 1;
                    if ($targetCalories > 0 && $nutrition['calories'] > 0) {
                        $mealRatio = $macrosConfig[$mealName]['ratio'] ?? 0.25;
                        $targetMealCals = $targetCalories * $mealRatio;
                        $multiplier = max(1, (int) round($targetMealCals / $nutrition['calories']));
                    }

                    $meals[] = [
                        'meal_type'        => $mealName,
                        'spoonacular_id'   => (int) $recipe['id'],
                        'title'            => $recipe['title'] ?? '',
                        'image'            => $recipe['image'] ?? '',
                        'ready_in_minutes' => (int) ($recipe['readyInMinutes'] ?? 0),
                        'servings'         => $multiplier,
                        'calories'         => $nutrition['calories'] * $multiplier,
                        'protein'          => $nutrition['protein'] * $multiplier,
                        'carbs'            => $nutrition['carbs'] * $multiplier,
                        'fat'              => $nutrition['fat'] * $multiplier,
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