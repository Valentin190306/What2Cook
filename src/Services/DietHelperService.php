<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class DietHelperService
{
    private SpoonacularService $spoonacular;

    // Mapeo del valor del formulario al parámetro que acepta Spoonacular
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

    // ── Punto de entrada principal ────────────────────────────────────────────

    /**
     * Genera un plan completo de N días.
     *
     * @param  int    $durationDays    7 | 14 | 30
     * @param  int    $targetCalories  Calorías diarias
     * @param  int    $targetProtein   Proteínas diarias en gramos
     * @param  int    $targetCarbs     Carbohidratos diarios en gramos
     * @param  int    $targetFat       Grasas diarias en gramos
     * @param  string $dietType        Valor del formulario (vegana, keto, etc.) o vacío
     * @return array  Plan estructurado con 'meta' y 'days'
     */
    public function generatePlan(
        int    $durationDays,
        int    $targetCalories,
        int    $targetProtein,
        int    $targetCarbs,
        int    $targetFat,
        string $dietType = ''
    ): array {
        $spoonacularDiet = self::DIET_MAP[$dietType] ?? '';
        $weeksNeeded     = (int) ceil($durationDays / 7);
        $days            = [];

        for ($week = 0; $week < $weeksNeeded; $week++) {
            $weekData  = $this->spoonacular->generateWeeklyPlan($targetCalories, $spoonacularDiet);
            $weekDays  = $this->extractDaysFromWeek($weekData, $week * 7);

            // Si la duración no es múltiplo de 7, recortamos los días sobrantes
            $remaining = $durationDays - count($days);
            $days      = array_merge($days, array_slice($weekDays, 0, $remaining));
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

    // ── Transformación de la respuesta de Spoonacular ─────────────────────────

    /**
     * Convierte la respuesta semanal de Spoonacular a un array de días
     * con el formato interno del proyecto.
     *
     * Spoonacular devuelve las comidas bajo keys como 'monday', 'tuesday', etc.
     * Cada día tiene 3 meals (breakfast, lunch, dinner).
     * Acá se agrega la merienda (snack) buscando una receta liviana adicional.
     */
    private function extractDaysFromWeek(array $weekData, int $dayOffset): array
    {
        $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $days     = [];

        foreach ($dayNames as $i => $dayName) {
            if (!isset($weekData['week'][$dayName])) {
                continue;
            }

            $rawDay   = $weekData['week'][$dayName];
            $meals    = $this->extractMeals($rawDay['meals'] ?? []);
            $snack    = $this->fetchSnack($meals);
            
            // Insertar la merienda antes de la última comida (Cena)
            $insertPos = max(0, count($meals) - 1);
            array_splice($meals, $insertPos, 0, [$snack]);

            $totals = $this->calculateDayTotals($meals);

            $days[] = [
                'day_index'      => $dayOffset + $i + 1,
                'total_calories' => $totals['calories'],
                'total_protein'  => $totals['protein'],
                'total_carbs'    => $totals['carbs'],
                'total_fat'      => $totals['fat'],
                'meals'          => $meals,
            ];
        }

        return $days;
    }

    /**
     * Mapea las meals crudas de Spoonacular al formato interno.
     * Spoonacular usa slot 1=breakfast, 2=lunch, 3=dinner.
     *
     * @param  array $rawMeals
     * @return array
     */
    private function extractMeals(array $rawMeals): array
    {
        $slotMap = [
            1 => 'breakfast',
            2 => 'lunch',
            3 => 'dinner',
        ];

        $meals = [];
        $index = 1;

        foreach ($rawMeals as $raw) {
            $slot      = $raw['slot'] ?? $index++;
            $mealType  = $slotMap[$slot] ?? 'lunch';
            $nutrition = $this->fetchNutrition((int) $raw['id']);

            $meals[] = [
                'meal_type'       => $mealType,
                'spoonacular_id'  => (int) $raw['id'],
                'title'           => $raw['title'] ?? '',
                'image'           => $this->buildImageUrl((int) $raw['id'], $raw['imageType'] ?? 'jpg'),
                'ready_in_minutes'=> (int) ($raw['readyInMinutes'] ?? 0),
                'servings'        => (int) ($raw['servings'] ?? 1),
                'calories'        => $nutrition['calories'],
                'protein'         => $nutrition['protein'],
                'carbs'           => $nutrition['carbs'],
                'fat'             => $nutrition['fat'],
            ];
        }

        return $meals;
    }

    /**
     * Busca una receta liviana para usar como merienda.
     * Usa calorías bajas y excluye los IDs ya presentes en el día.
     *
     * @param  array $existingMeals Meals ya asignadas al día (para excluir sus IDs)
     * @return array
     */
    private function fetchSnack(array $existingMeals): array
    {
        $excludeIds = array_column($existingMeals, 'spoonacular_id');

        $results = $this->spoonacular->searchRecipes([
            'maxCalories' => 300,
            'minCalories' => 100,
            'type'        => 'breakfast',
            'number'      => 5,
        ]);

        $recipes = $results['results'] ?? [];

        // Buscar una que no esté ya en el día
        $chosen = null;
        foreach ($recipes as $recipe) {
            if (!in_array((int) $recipe['id'], $excludeIds, true)) {
                $chosen = $recipe;
                break;
            }
        }

        // Si no encontró ninguna distinta, usa la primera disponible
        if ($chosen === null) {
            $chosen = $recipes[0] ?? null;
        }

        if ($chosen === null) {
            return $this->emptyMeal('snack');
        }

        $nutrition = $this->extractNutritionFromRecipe($chosen);

        return [
            'meal_type'        => 'snack',
            'spoonacular_id'   => (int) $chosen['id'],
            'title'            => $chosen['title'] ?? '',
            'image'            => $chosen['image'] ?? '',
            'ready_in_minutes' => (int) ($chosen['readyInMinutes'] ?? 0),
            'servings'         => (int) ($chosen['servings'] ?? 1),
            'calories'         => $nutrition['calories'],
            'protein'          => $nutrition['protein'],
            'carbs'            => $nutrition['carbs'],
            'fat'              => $nutrition['fat'],
        ];
    }

    // ── Nutrición ─────────────────────────────────────────────────────────────

    /**
     * Obtiene macros de una receta por su ID.
     * Usa el endpoint nutritionWidget que devuelve los valores directamente.
     *
     * @param  int $id
     * @return array{calories: float, protein: float, carbs: float, fat: float}
     */
    private function fetchNutrition(int $id): array
    {
        try {
            $data = $this->spoonacular->getRecipeNutrition($id);
            return $this->parseNutritionWidget($data);
        } catch (RuntimeException) {
            return ['calories' => 0.0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        }
    }

    /**
     * Parsea la respuesta del nutritionWidget de Spoonacular.
     * Los valores vienen como strings con unidad: "320 calories", "15g", etc.
     */
    private function parseNutritionWidget(array $data): array
    {
        return [
            'calories' => (float) ($data['calories'] ?? 0),
            'protein'  => (float) filter_var($data['protein'] ?? '0', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'carbs'    => (float) filter_var($data['carbs']   ?? '0', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'fat'      => (float) filter_var($data['fat']     ?? '0', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        ];
    }

    /**
     * Extrae nutrición desde la respuesta de complexSearch (que incluye nutrition inline).
     */
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

    // ── Utilidades ────────────────────────────────────────────────────────────

    /**
     * Suma los macros de todas las comidas de un día.
     */
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

    /**
     * Construye la URL de imagen de Spoonacular a partir del ID y tipo
     */
    private function buildImageUrl(int $id, string $imageType): string
    {
        return "https://img.spoonacular.com/recipes/{$id}-556x370.{$imageType}";
    }

    /**
     * Devuelve una meal vacía para cuando no se encuentra snack
     */
    private function emptyMeal(string $mealType): array
    {
        return [
            'meal_type'        => $mealType,
            'spoonacular_id'   => 0,
            'title'            => '',
            'image'            => '',
            'ready_in_minutes' => 0,
            'servings'         => 0,
            'calories'         => 0.0,
            'protein'          => 0.0,
            'carbs'            => 0.0,
            'fat'              => 0.0,
        ];
    }
}