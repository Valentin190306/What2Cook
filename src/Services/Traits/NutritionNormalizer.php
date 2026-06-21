<?php
declare(strict_types=1);

namespace App\Services\Traits;

trait NutritionNormalizer
{
    private const SPANISH_NUTRIENTS = [
        'Calorías'      => 'Calories',
        'Energía'       => 'Calories',
        'Proteínas'     => 'Protein',
        'Proteina'      => 'Protein',
        'Carbohidratos' => 'Carbohydrates',
        'Grasa'         => 'Fat',
        'Grasas'        => 'Fat',
        'Grasas Totales' => 'Fat',
        'Azúcar'        => 'Sugar',
        'Azúcares'      => 'Sugar',
        'Fibra'         => 'Fiber',
        'Fibra Alimentaria' => 'Fiber',
        'Colesterol'    => 'Cholesterol',
        'Sodio'         => 'Sodium',
    ];

    /**
     * Calcula la estructura plana de nutrición (calories, protein, carbs, fat)
     * a partir de nutrition.nutrients. Normaliza nombres de nutrientes al inglés
     * por si algún registro antiguo en DB aún los tiene traducidos.
     */
    private function normalizeRecipe(array $recipe): array
    {
        $nutrients = $recipe['nutrition']['nutrients'] ?? [];

        foreach ($nutrients as $i => $n) {
            if (isset(self::SPANISH_NUTRIENTS[$n['name']])) {
                $nutrients[$i]['name'] = self::SPANISH_NUTRIENTS[$n['name']];
            }
        }

        $map = [];
        foreach ($nutrients as $n) {
            $map[$n['name']] = (float) ($n['amount'] ?? 0);
        }

        $recipe['nutrition'] = [
            'nutrients' => $nutrients,
            'calories'  => $map['Calories']        ?? 0.0,
            'protein'   => $map['Protein']          ?? 0.0,
            'carbs'     => $map['Carbohydrates']    ?? 0.0,
            'fat'       => $map['Fat']              ?? 0.0,
        ];

        return $recipe;
    }
}
