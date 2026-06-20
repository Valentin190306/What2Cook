<?php
declare(strict_types=1);

namespace App\Services\Translation;

use App\Core\Log\LoggerInterface;
use App\Models\RecipeTranslation;
use App\Services\SpoonacularService;
use RuntimeException;

class RecipePreloadJob
{
    private const POINTS_LOG = __DIR__ . '/../../../log/.spoonacular_points.json';

    private const DISCOVERY_DIETS = [
        'vegetarian', 'vegan', 'gluten-free', 'ketogenic', 'paleo',
        'whole30', 'pescatarian', 'primal', 'lacto-vegetarian', 'ovo-vegetarian',
        'dairy-free',
    ];

    private const DISCOVERY_CUISINES = [
        'italian', 'mexican', 'asian', 'american', 'mediterranean',
        'french', 'thai', 'spanish', 'indian', 'chinese', 'japanese',
        'greek', 'middle-eastern',
    ];

    private const DISCOVERY_TYPES = [
        'main course', 'dessert', 'breakfast', 'lunch', 'dinner',
        'snack', 'appetizer', 'salad', 'soup', 'side dish',
    ];

    private SpoonacularService $api;
    private LibreTranslateTranslator $translator;
    private RecipeTranslation $model;
    private ?LoggerInterface $logger;

    private int $pointsUsed = 0;
    private int $maxPoints;
    private int $searchTermsPerRun;

    public function __construct(
        string $apiKey,
        ?int $maxPoints = null,
        ?int $searchTermsPerRun = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger;
        $this->api = new SpoonacularService($logger, $apiKey);
        $this->translator = new LibreTranslateTranslator($logger);
        $this->model = new RecipeTranslation();

        $this->maxPoints = $maxPoints ?? (int) ($_ENV['TRANSLATION_DAILY_LIMIT'] ?? 150);
        $this->searchTermsPerRun = $searchTermsPerRun ?? (int) ($_ENV['TRANSLATION_SEARCH_TERMS'] ?? 5);
    }

    /**
     * Ejecuta el job de precarga.
     *
     * @return array{translated: int, points_used: int, reason: string, elapsed_sec?: float}
     */
    public function run(bool $dryRun = false): array
    {
        $start = microtime(true);
        $this->log('info', 'Iniciando job de precarga', [
            'max_points' => $this->maxPoints,
            'dry_run' => $dryRun,
        ]);

        $dailyUsage = $this->getDailyPoints();
        $available = max(0, $this->maxPoints - $dailyUsage);

        if ($available <= 0) {
            $this->log('info', 'Cuota diaria agotada', ['used' => $dailyUsage, 'max' => $this->maxPoints]);
            return ['translated' => 0, 'points_used' => 0, 'reason' => 'quota_exhausted'];
        }

        $this->log('info', 'Puntos disponibles hoy', ['used' => $dailyUsage, 'available' => $available]);

        $discoveredIds = $this->discoverRecipeIds($available, $dryRun);
        $this->log('info', 'Descubrimiento completado', [
            'new_ids' => count($discoveredIds),
            'points_used' => $this->pointsUsed,
        ]);

        if (empty($discoveredIds)) {
            return ['translated' => 0, 'points_used' => $this->pointsUsed, 'reason' => 'no_new_ids'];
        }

        $pointsRemaining = $available - $this->pointsUsed;
        $translated = $this->fetchAndTranslate($discoveredIds, $pointsRemaining, $dryRun);

        $this->saveDailyPoints($this->pointsUsed);
        $this->maybeRotateSearchIndex();

        $elapsed = round(microtime(true) - $start, 2);
        return [
            'translated' => $translated,
            'points_used' => $this->pointsUsed,
            'reason' => $dryRun ? 'dry_run' : ($translated > 0 ? 'ok' : 'no_translations'),
            'elapsed_sec' => $elapsed,
        ];
    }

    // ── Descubrimiento ──────────────────────────────────────────────────────

    /**
     * Descubre IDs de recetas no traducidas usando complexSearch.
     * Usa un subconjunto rotativo de términos de búsqueda.
     */
    private function discoverRecipeIds(int $availablePoints, bool $dryRun): array
    {
        $allTerms = $this->buildSearchTerms();
        $index = $this->getSearchIndex();
        $selectedTerms = [];

        for ($i = 0; $i < $this->searchTermsPerRun; $i++) {
            $idx = ($index + $i) % count($allTerms);
            $selectedTerms[] = $allTerms[$idx];
        }

        $this->log('info', 'Términos de búsqueda seleccionados', [
            'terms' => $selectedTerms,
            'total_pool' => count($allTerms),
        ]);

        $alreadyTranslated = $this->model->findAllIds();
        $alreadyTranslatedSet = array_flip($alreadyTranslated);
        $newIds = [];

        foreach ($selectedTerms as $term) {
            $remaining = $availablePoints - $this->pointsUsed;
            if ($remaining < 1) break;

            $this->log('info', 'Buscando recetas', ['term' => $term]);

            if ($dryRun) {
                $this->pointsUsed++;
                continue;
            }

            try {
                $results = $this->api->searchRecipes([
                    'query' => $term,
                    'number' => 100,
                    'addRecipeInformation' => 'true',
                    'addRecipeNutrition' => 'true',
                ]);
                $this->pointsUsed++;
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), '402')) {
                    $this->log('warning', 'Cuota Spoonacular agotada durante descubrimiento');
                    break;
                }
                $this->log('error', 'Error en complexSearch', ['term' => $term, 'error' => $e->getMessage()]);
                $this->pointsUsed++;
                continue;
            }

            $items = $results['results'] ?? [];
            foreach ($items as $item) {
                $sid = (int) ($item['id'] ?? 0);
                if ($sid > 0 && !isset($alreadyTranslatedSet[$sid])) {
                    $newIds[$sid] = $sid;
                }
            }
        }

        return array_values($newIds);
    }

    /**
     * Construye la lista completa de términos de búsqueda combinando
     * dietas, cocinas y tipos de plato.
     */
    private function buildSearchTerms(): array
    {
        $terms = [];
        foreach (self::DISCOVERY_DIETS as $d) {
            $terms[] = "diet:{$d}";
        }
        foreach (self::DISCOVERY_CUISINES as $c) {
            $terms[] = "cuisine:{$c}";
        }
        foreach (self::DISCOVERY_TYPES as $t) {
            $terms[] = "type:{$t}";
        }
        return $terms;
    }

    // ── Obtención y traducción ───────────────────────────────────────────────

    /**
     * Obtiene el detalle completo de las recetas, traduce y guarda en DB.
     * Procesa en batches de 10 (informationBulk).
     */
    private function fetchAndTranslate(array $ids, int $availablePoints, bool $dryRun): int
    {
        $maxBulkCalls = min(
            (int) floor($availablePoints / 1),
            (int) ceil(count($ids) / 10)
        );
        $totalToProcess = min($maxBulkCalls * 10, count($ids));
        $ids = array_slice($ids, 0, $totalToProcess);

        if (empty($ids)) return 0;

        $this->log('info', 'Iniciando fetch + traducción', [
            'total_ids' => count($ids),
            'max_bulk_calls' => $maxBulkCalls,
        ]);

        $batches = array_chunk($ids, 10);
        $translated = 0;

        foreach ($batches as $batch) {
            $remaining = $availablePoints - $this->pointsUsed;
            if ($remaining < 1) {
                $this->log('info', 'Puntos agotados, deteniendo fetch');
                break;
            }

            $this->log('info', 'Obteniendo batch', ['ids' => $batch]);

            if ($dryRun) {
                $this->pointsUsed++;
                $translated += count($batch);
                continue;
            }

            try {
                $recipes = $this->api->getRawRecipeInfoBulk($batch, true);
                $this->pointsUsed++;
            } catch (RuntimeException $e) {
                if (str_contains($e->getMessage(), '402')) {
                    $this->log('warning', 'Cuota Spoonacular agotada');
                    break;
                }
                $this->log('error', 'Error en informationBulk', ['error' => $e->getMessage()]);
                $this->pointsUsed++;
                continue;
            }

            foreach ($recipes as $recipeData) {
                if (!is_array($recipeData) || empty($recipeData['id'])) continue;

                try {
                    $this->translateAndStore($recipeData);
                    $translated++;
                } catch (\Throwable $e) {
                    $this->log('error', 'Error traduciendo/guardando receta', [
                        'id' => $recipeData['id'] ?? '?',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $translated;
    }

    /**
     * Traduce una receta completa y la guarda en la BD.
     */
    private function translateAndStore(array $recipeData): void
    {
        $sid = (int) $recipeData['id'];
        $title = $recipeData['title'] ?? '';
        $summary = $recipeData['summary'] ?? '';
        $image = $recipeData['image'] ?? null;

        $rawEn = json_encode($recipeData, JSON_UNESCAPED_UNICODE);

        // Traducir campos principales
        $titleEs = $title !== '' ? $this->translator->translate($title, 'es') : '';
        $summaryEs = $summary !== '' ? $this->translator->translate(
            html_entity_decode(strip_tags($summary), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'es'
        ) : '';

        // Traducir array completo (incluye instructions, ingredients, etc.)
        $translatedData = $this->translator->translateArray($recipeData, 'es');
        $rawEs = json_encode($translatedData, JSON_UNESCAPED_UNICODE);

        $this->model->upsert([
            'spoonacular_id'   => $sid,
            'title_en'         => $title,
            'title_es'         => $titleEs,
            'summary_en'       => $summary,
            'summary_es'       => $summaryEs,
            'raw_response_en'  => $rawEn,
            'raw_response_es'  => $rawEs,
            'image'            => $image,
            'ready_in_minutes' => $recipeData['readyInMinutes'] ?? null,
            'servings'         => $recipeData['servings'] ?? null,
            'cuisines'         => !empty($recipeData['cuisines']) ? json_encode($recipeData['cuisines']) : null,
            'diets'            => !empty($recipeData['diets']) ? json_encode($recipeData['diets']) : null,
            'dish_types'       => !empty($recipeData['dishTypes']) ? json_encode($recipeData['dishTypes']) : null,
        ]);
    }

    // ── Control de cuota ─────────────────────────────────────────────────────

    private function getDailyPoints(): int
    {
        $data = $this->readPointsFile();
        $today = date('Y-m-d');
        if (($data['date'] ?? '') !== $today) {
            return 0;
        }
        return (int) ($data['points'] ?? 0);
    }

    private function saveDailyPoints(int $points): void
    {
        $existing = $this->readPointsFile();
        $today = date('Y-m-d');
        $total = (($existing['date'] ?? '') === $today) ? ((int) ($existing['points'] ?? 0) + $points) : $points;

        file_put_contents(
            self::POINTS_LOG,
            json_encode(['date' => $today, 'points' => $total], JSON_UNESCAPED_UNICODE)
        );
    }

    private function readPointsFile(): array
    {
        if (!file_exists(self::POINTS_LOG)) {
            return ['date' => '', 'points' => 0];
        }
        $raw = file_get_contents(self::POINTS_LOG);
        if ($raw === false) return ['date' => '', 'points' => 0];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['date' => '', 'points' => 0];
    }

    // ── Rotación de términos de búsqueda ─────────────────────────────────────

    private const INDEX_FILE = __DIR__ . '/../../../log/.search_index.txt';

    private function getSearchIndex(): int
    {
        if (!file_exists(self::INDEX_FILE)) return 0;
        $raw = file_get_contents(self::INDEX_FILE);
        return max(0, (int) $raw);
    }

    private function maybeRotateSearchIndex(): void
    {
        $total = count($this->buildSearchTerms());
        $next = ($this->getSearchIndex() + $this->searchTermsPerRun) % $total;
        file_put_contents(self::INDEX_FILE, (string) $next);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $this->logger->log($level, "[RecipePreloadJob] {$message}", $context);
    }
}
