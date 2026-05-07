<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class SpoonacularService
{
    private const BASE_URL = 'https://api.spoonacular.com';

    private string $apiKey;

    public function __construct()
    {
        $key = $_ENV['SPOONACULAR_KEY'] ?? '';
        if ($key === '') {
            throw new RuntimeException('SPOONACULAR_KEY no está definida en las variables de entorno.');
        }
        $this->apiKey = $key;
    }

    // ── Meal Planner ─────────────────────────────────────────────────────────

    /**
     * Genera un plan de comidas semanal desde Spoonacular.
     */
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

    /**
     * Busca recetas por ingredientes disponibles.
     */
    public function searchByIngredients(array $ingredients, int $number = 10, bool $maximize = true): array
    {
        $ingredients = $this->maybeTranslateInput($ingredients);

        return $this->get('/recipes/findByIngredients', [
            'ingredients'          => implode(',', $ingredients),
            'number'               => $number,
            'ranking'              => $maximize ? 2 : 1,
            'ignorePantry'         => true,
        ]);
    }

    /**
     * Obtiene información completa de una receta (incluye nutrición).
     */
    public function getRecipeInfo(int $id, bool $includeNutrition = true): array
    {
        return $this->get("/recipes/{$id}/information", [
            'includeNutrition' => $includeNutrition ? 'true' : 'false',
        ]);
    }

    /**
     * Obtiene información nutricional de una receta.
     */
    public function getRecipeNutrition(int $id): array
    {
        return $this->get("/recipes/{$id}/nutritionWidget.json", []);
    }

    /**
     * Busca recetas con filtros generales.
     */
    public function searchRecipes(array $filters = []): array
    {
        $defaults = [
            'addRecipeNutrition' => true,
            'number'             => 12,
        ];

        $filters = array_merge($defaults, $filters);

        // Traducir campos de búsqueda si existen
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

        return $this->get('/recipes/complexSearch', $filters);
    }


    // ── HTTP ──────────────────────────────────────────────────────────────────

    /**
     * GET a la API de Spoonacular.
     */
    private function get(string $endpoint, array $params): array
    {
        $params['apiKey'] = $this->apiKey;

        $url = self::BASE_URL . $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
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

        $enableTranslation = ($_ENV['ENABLE_OUTPUT_TRANSLATION'] ?? 'false') === 'true';
        if ($enableTranslation) {
            try {
                $translator = $this->getTranslator();
                $data = $translator->translateArray($data, 'es');
            } catch (\Exception $e) {
                error_log("Error de traducción (output): " . $e->getMessage());
            }
        }

        return $data;
    }

    /**
     * Traduce el input (ES -> EN) si la traducción está habilitada.
     */
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
            error_log("Error de traducción (input): " . $e->getMessage());
            return $input;
        }
    }

    /**
     * Helper para instanciar el traductor configurado.
     */
    private function getTranslator(): \App\Services\Translation\TranslatorInterface
    {
        $provider = strtolower($_ENV['TRANSLATION_PROVIDER'] ?? 'gemini');
        if ($provider === 'openai') {
            return new \App\Services\Translation\OpenAITranslator();
        }
        return new \App\Services\Translation\GeminiTranslator();
    }
}