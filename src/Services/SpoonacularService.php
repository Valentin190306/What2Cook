<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\FileCache;
use App\Core\Log\LoggerInterface;
use App\Services\Traits\NutritionNormalizer;
use RuntimeException;

class SpoonacularService
{
    use NutritionNormalizer;
    private const BASE_URL = 'https://api.spoonacular.com';
    private const CACHE_TTL = 604800; // 7 días
    private const API_TIMEOUT = 20;

    private string $apiKey;
    private ?LoggerInterface $logger = null;
    private FileCache $cache;

    /**
     * @param string|null $apiKey  Key opcional. Si no se pasa, usa SPOONACULAR_KEY del .env.
     */
    public function __construct(?LoggerInterface $logger = null, ?string $apiKey = null)
    {
        $this->logger = $logger;

        $key = $apiKey ?? ($_ENV['SPOONACULAR_KEY'] ?? '');
        if ($key === '') {
            throw new RuntimeException(
                'SPOONACULAR_KEY no está definida. Pasa una key al constructor o define la env var.'
            );
        }
        $this->apiKey = $key;

        $this->cache = new FileCache(
            __DIR__ . '/../../log/cache',
            self::CACHE_TTL,
            $this->logger
        );
    }

    // ── Meal Planner (descontinuado) ─────────────────────────────────────────

    public function generateWeeklyPlan(int $targetCalories, string $diet = '', string $exclude = ''): array
    {
        $params = ['targetCalories' => $targetCalories];

        if ($diet !== '') {
            $params['diet'] = $diet;
        }
        if ($exclude !== '') {
            $params['exclude'] = $exclude;
        }

        return $this->get('/mealplanner/generate', $params + ['timeFrame' => 'week']);
    }

    // ── Recetas ───────────────────────────────────────────────────────────────

    public function searchByIngredients(array $ingredients, int $number = 10, bool $maximize = true, bool $skipTranslation = false): array
    {
        $ingredients = $this->maybeTranslateInput($ingredients);

        $data = $this->get('/recipes/findByIngredients', [
            'ingredients'          => implode(',', $ingredients),
            'number'               => $number,
            'ranking'              => $maximize ? 2 : 1,
            'ignorePantry'         => 'true',
        ]);

        if ($skipTranslation) return $data;

        return $this->translateResults($data);
    }

    public function getRecipeInfo(int $id, bool $includeNutrition = true, bool $translate = true): array
    {
        $data = $this->get("/recipes/{$id}/information", [
            'includeNutrition' => $includeNutrition ? 'true' : 'false',
        ]);

        if (!$translate) return $data;

        $translated = $this->translateResults([$data]);
        return $translated[0] ?? $data;
    }

    public function getRecipeInfoBulk(array $ids, bool $includeNutrition = true, bool $translate = true): array
    {
        if (empty($ids)) return [];

        $data = $this->get("/recipes/informationBulk", [
            'ids' => implode(',', $ids),
            'includeNutrition' => $includeNutrition ? 'true' : 'false',
        ]);

        if ($translate && is_array($data)) {
            return $this->translateResults($data);
        }

        return $data;
    }

    /**
     * Obtiene datos de receta SIN traducir (para el job de precarga).
     */
    public function getRawRecipeInfo(int $id, bool $includeNutrition = true): array
    {
        return $this->get("/recipes/{$id}/information", [
            'includeNutrition' => $includeNutrition ? 'true' : 'false',
        ]);
    }

    /**
     * Obtiene datos de varias recetas SIN traducir (para el job de precarga).
     */
    public function getRawRecipeInfoBulk(array $ids, bool $includeNutrition = true): array
    {
        if (empty($ids)) return [];
        return $this->get("/recipes/informationBulk", [
            'ids' => implode(',', $ids),
            'includeNutrition' => $includeNutrition ? 'true' : 'false',
        ]);
    }

    public function getRecipeNutrition(int $id): array
    {
        return $this->get("/recipes/{$id}/nutritionWidget.json", []);
    }

    public function searchRecipes(array $filters = [], bool $skipTranslation = false): array
    {
        $defaults = [
            'addRecipeNutrition' => 'true',
            'number'             => 12,
        ];

        $filters = array_merge($defaults, $filters);

        if (isset($filters['query'])) {
            $filters['query'] = $this->maybeTranslateInput($filters['query']);
        }

        if (isset($filters['includeIngredients'])) {
            $ingredients = explode(',', $filters['includeIngredients']);
            $translated = $this->maybeTranslateInput($ingredients);
            $filters['includeIngredients'] = implode(',', $translated);
        }

        if (isset($filters['excludeIngredients'])) {
            $ingredients = explode(',', $filters['excludeIngredients']);
            $translated = $this->maybeTranslateInput($ingredients);
            $filters['excludeIngredients'] = implode(',', $translated);
        }

        $data = $this->get('/recipes/complexSearch', $filters);

        if (!$skipTranslation && isset($data['results']) && is_array($data['results'])) {
            $data['results'] = $this->translateResults($data['results']);
        }

        return $data;
    }

    // ── Traducción granular por receta ───────────────────────────────────────

    private function translateResults(array $items): array
    {
        $enableTranslation = ($_ENV['ENABLE_OUTPUT_TRANSLATION'] ?? 'false') === 'true';
        if (!$enableTranslation) return $items;

        $translator = $this->getTranslator();

        foreach ($items as &$item) {
            if (!is_array($item) || !isset($item['id'])) continue;

            try {
                $nutrition = $item['nutrition'] ?? null;
                $item = $translator->translateArray($item, 'es');
                if ($nutrition !== null) {
                    $item['nutrition'] = $nutrition;
                }
                $item = $this->normalizeRecipe($item);
            } catch (\Exception $e) {
                $this->log('warning', "Error traduciendo receta {$item['id']}: " . $e->getMessage());
            }
        }

        return $items;
    }

    // ── HTTP ──────────────────────────────────────────────────────────────────

    private function get(string $endpoint, array $params): array
    {
        $cacheKey = $this->buildCacheKey($endpoint, $params);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $params['apiKey'] = $this->apiKey;

        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::API_TIMEOUT,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $body === false) {
            throw new RuntimeException("Error de red al llamar a Spoonacular: cURL errno {$errno}");
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Respuesta inválida de Spoonacular: no es JSON.');
        }

        if ($httpCode === 402) {
            throw new RuntimeException('Límite diario de Spoonacular alcanzado (402).');
        }

        if ($httpCode >= 400) {
            $message = $data['message'] ?? 'Error desconocido';
            throw new RuntimeException("Spoonacular respondió {$httpCode}: {$message}");
        }

        $this->cache->set($cacheKey, $data);

        return $data;
    }

    private function buildCacheKey(string $endpoint, array $params): string
    {
        return $endpoint . '?' . http_build_query($params);
    }

    private function maybeTranslateInput(array|string $input): array|string
    {
        $enableTranslation = ($_ENV['ENABLE_INPUT_TRANSLATION'] ?? 'false') === 'true';
        if (!$enableTranslation) {
            return $input;
        }

        try {
            $translator = $this->getTranslator();
            if (is_array($input)) {
                return $translator->translateArray($input, 'en');
            }
            return $translator->translate($input, 'en');
        } catch (\Exception $e) {
            $this->log('error', "Error de traducción (input): " . $e->getMessage());
            return $input;
        }
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $module = (new \ReflectionClass($this))->getShortName();
        $this->logger->log($level, "[{$module}] {$message}", $context);
    }

    private function getTranslator(): \App\Services\Translation\TranslatorInterface
    {
        $provider = strtolower($_ENV['TRANSLATION_PROVIDER'] ?? 'libretranslate');

        $inner = match ($provider) {
            'openai' => new \App\Services\Translation\OpenAITranslator($this->logger),
            'gemini' => new \App\Services\Translation\GeminiTranslator($this->logger),
            default => new \App\Services\Translation\LibreTranslateTranslator($this->logger),
        };

        return new \App\Services\Translation\CachedTranslator($inner, null, $this->logger);
    }
}
