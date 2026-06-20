<?php
declare(strict_types=1);

namespace App\Services\Traits;

trait NutritionNormalizer
{
    /**
     * Normaliza la sección nutrition de una receta:
     *  1. Restaura nombres de nutrientes al inglés usando los datos originales
     *  2. Calcula la estructura plana (calories, protein, carbs, fat)
     *
     * @param array      $recipe    Datos de la receta (posiblemente traducidos)
     * @param array|null $original  Datos originales en inglés (para restaurar nombres)
     * @return array
     */
    private function normalizeRecipe(array $recipe, ?array $original = null): array
    {
        $nutrients = $recipe['nutrition']['nutrients'] ?? [];

        if ($original !== null && isset($original['nutrition']['nutrients'])) {
            foreach ($original['nutrition']['nutrients'] as $i => $n) {
                if (isset($nutrients[$i])) {
                    $nutrients[$i]['name'] = $n['name'];
                }
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
