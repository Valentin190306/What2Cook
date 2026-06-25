<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Log\LoggerInterface;
use App\Models\RecipeTranslation;
use App\Services\Traits\NutritionNormalizer;

class RecipeStorageService
{
    use NutritionNormalizer;

    private ?LoggerInterface $logger;
    private SpoonacularService $spoonacular;

    public function __construct(?LoggerInterface $logger = null, ?SpoonacularService $spoonacular = null)
    {
        $this->logger = $logger;
        $this->spoonacular = $spoonacular ?? new SpoonacularService($logger);
    }

    /**
     * Valida que los datos decodificados tengan la estructura completa de Spoonacular.
     */
    public function isCompleteRecipe(?array $data): bool
    {
        return $data !== null && isset($data['extendedIngredients']);
    }

    /**
     * Busca una receta en la BD local.
     * Devuelve raw_response_es (traducido), raw_response_en (inglés fallback) o null.
     */
    public function findLocal(int $spoonacularId): ?array
    {
        $enabled = ($_ENV['RECIPES_TABLE_ENABLED'] ?? 'true') === 'true';
        if (!$enabled) return null;

        try {
            $row = (new RecipeTranslation())->findBySpoonacularId($spoonacularId);
            if ($row) {
                if (!empty($row['raw_response_es'])) {
                    $decoded = json_decode($row['raw_response_es'], true);
                    if (is_array($decoded) && $this->isCompleteRecipe($decoded)) {
                        return $this->normalizeRecipe($decoded);
                    }
                }
                $originalEn = $row['raw_response_en'] ? json_decode($row['raw_response_en'], true) : null;
                if ($originalEn !== null && $this->isCompleteRecipe($originalEn)) {
                    return $this->normalizeRecipe($originalEn);
                }
            }
        } catch (\Throwable $e) {
            $this->log('error', 'Error consultando recipe_translations', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Para una lista de spoonacular_ids, resuelve cada receta desde la BD local
     * o desde Spoonacular API como fallback.
     *
     * @param array $spoonacularIds
     * @param array &$needsTranslation Se llena con los IDs que requieren traducción background
     * @return array
     */
    public function resolveBulk(array $spoonacularIds, array &$needsTranslation = []): array
    {
        $enabled = ($_ENV['RECIPES_TABLE_ENABLED'] ?? 'true') === 'true';
        $result = [];
        $missingIds = [];

        if ($enabled) {
            $model = new RecipeTranslation();
            foreach ($spoonacularIds as $sid) {
                $sid = (int) $sid;
                try {
                    $row = $model->findBySpoonacularId($sid);
                    if ($row) {
                        if (!empty($row['raw_response_es'])) {
                            $decoded = json_decode($row['raw_response_es'], true);
                            if (is_array($decoded) && $this->isCompleteRecipe($decoded)) {
                                $result[] = $this->normalizeRecipe($decoded);
                                continue;
                            }
                        }
                        $originalEn = $row['raw_response_en'] ? json_decode($row['raw_response_en'], true) : null;
                        if ($originalEn !== null && $this->isCompleteRecipe($originalEn)) {
                            $result[] = $this->normalizeRecipe($originalEn);
                            $needsTranslation[] = $sid;
                            continue;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log('error', 'Error consultando recipe_translations', ['id' => $sid, 'error' => $e->getMessage()]);
                }
                $missingIds[] = $sid;
            }
        } else {
            $missingIds = $spoonacularIds;
        }

        if (!empty($missingIds)) {
            try {
                $fetched = $this->spoonacular->getRecipeInfoBulk($missingIds, true, false);
                foreach ($fetched as $recipe) {
                    $sid = (int) ($recipe['id'] ?? 0);
                    (new RecipeTranslation())->saveRaw($sid, $recipe);
                    $result[] = $recipe;
                    $needsTranslation[] = $sid;
                }
            } catch (\Throwable $e) {
                $this->log('error', 'Error fetching bulk recipe info', ['error' => $e->getMessage()]);
                foreach ($missingIds as $sid) {
                    $result[] = [
                        'id' => $sid,
                        'title' => '',
                        'image' => null,
                        'readyInMinutes' => null,
                        'servings' => null,
                        'diets' => [],
                        'dishTypes' => [],
                        'nutrition' => ['nutrients' => []],
                    ];
                }
            }
        }

        $ordered = [];
        $resultMap = [];
        foreach ($result as $r) {
            $resultMap[(int) ($r['id'] ?? 0)] = $r;
        }
        foreach ($spoonacularIds as $sid) {
            if (isset($resultMap[(int) $sid])) {
                $ordered[] = $resultMap[(int) $sid];
            }
        }

        foreach ($ordered as &$recipe) {
            $recipe = $this->normalizeRecipe($recipe);
        }
        unset($recipe);

        return $ordered;
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $module = (new \ReflectionClass($this))->getShortName();
        $this->logger->log($level, "[{$module}] {$message}", $context);
    }
}
