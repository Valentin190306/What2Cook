<?php
declare(strict_types=1);

namespace App\Services\Translation;

use App\Models\RecipeTranslation;

class DeferredTranslator
{
    /**
     * Envía la respuesta al cliente y ejecuta $fn en background.
     */
    public static function afterResponse(callable $fn): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            fastcgi_finish_request();
        }
        $fn();
    }

    /**
     * Traduce una receta en background usando sus datos raw_response_en.
     * Solo traduce si raw_response_es aún no existe.
     */
    public static function translate(int $spoonacularId): void
    {
        $model = new RecipeTranslation();
        $row = $model->findBySpoonacularId($spoonacularId);

        if (!$row || !empty($row['raw_response_es'])) return;

        $recipeData = json_decode($row['raw_response_en'], true);
        if (!is_array($recipeData)) return;

        try {
            $translator = new LibreTranslateTranslator();

            $title = $recipeData['title'] ?? '';
            $summary = $recipeData['summary'] ?? '';

            $titleEs = $title !== '' ? $translator->translate($title, 'es') : '';
            $summaryEs = $summary !== '' ? $translator->translate(
                html_entity_decode(strip_tags($summary), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'es'
            ) : '';

            $nutrition = $recipeData['nutrition'] ?? null;
            $translatedData = $translator->translateArray($recipeData, 'es');
            if ($nutrition !== null) {
                $translatedData['nutrition'] = $nutrition;
            }
            $rawEs = json_encode($translatedData, JSON_UNESCAPED_UNICODE);

            $model->upsert([
                'spoonacular_id'  => $spoonacularId,
                'title_es'        => $titleEs,
                'summary_es'      => $summaryEs,
                'raw_response_es' => $rawEs,
            ]);
        } catch (\Throwable $e) {
            error_log("[DeferredTranslator] Error traduciendo {$spoonacularId}: " . $e->getMessage());
        }
    }
}
