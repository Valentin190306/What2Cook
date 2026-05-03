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

        return $this->get('/recipes/complexSearch', array_merge($defaults, $filters));
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

        return $data;
    }
}