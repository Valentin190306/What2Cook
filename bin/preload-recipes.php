#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Precarga y traduce recetas desde Spoonacular a la base de datos local.
 *
 * Descubre recetas nuevas mediante complexSearch combinando dietas, cocinas
 * y tipos de plato, obtiene el detalle completo con informationBulk, traduce
 * todo con LibreTranslate y lo guarda en recipe_translations.
 *
 * Usa SPOONACULAR_KEY_BACKGROUND (separada de la key de producción) para
 * no consumir la cuota del frontend. Controla el gasto diario de puntos
 * mediante log/.spoonacular_points.json y rota los términos de búsqueda
 * con log/.search_index.txt para cubrir todo el catálogo progresivamente.
 *
 * Uso:
 *   php bin/preload-recipes.php                          # ejecución normal
 *   php bin/preload-recipes.php --dry-run                 # solo descubre, no traduce
 *   php bin/preload-recipes.php --max-points=50           # límite personalizado
 *   php bin/preload-recipes.php --search-terms=3          # términos por ejecución
 *
 * Ejemplo cron (cada 2 días a las 3 AM):
 *   crontab: 0 3 * /2 * * php /var/www/app/bin/preload-recipes.php >> /var/www/app/log/preload-cron.log 2>&1
 */

// ── Bootstrap ────────────────────────────────────────────────────────────────

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    '/var/www/app/vendor/autoload.php',
];

foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);

$logger = new \App\Core\Log\FileLogger(__DIR__ . '/../log/app.log');
\App\Core\Database::getInstance();

// ── Parsear argumentos ───────────────────────────────────────────────────────

$dryRun = in_array('--dry-run', $argv, true);

$maxPoints = 150;
$searchTerms = 5;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--max-points=')) {
        $maxPoints = max(1, (int) substr($arg, strlen('--max-points=')));
    }
    if (str_starts_with($arg, '--search-terms=')) {
        $searchTerms = max(1, (int) substr($arg, strlen('--search-terms=')));
    }
}

// ── API key ──────────────────────────────────────────────────────────────────

$apiKey = $_ENV['SPOONACULAR_KEY_BACKGROUND'] ?? '';
if ($apiKey === '') {
    $logger->log('error', '[preload-recipes] SPOONACULAR_KEY_BACKGROUND no está definida en .env');
    echo "ERROR: Define SPOONACULAR_KEY_BACKGROUND en .env\n";
    exit(1);
}

// ── Ejecutar job ─────────────────────────────────────────────────────────────

$logger->log('info', '[preload-recipes] Iniciando', [
    'max_points' => $maxPoints,
    'search_terms' => $searchTerms,
    'dry_run' => $dryRun,
]);

echo sprintf(
    "RecipePreloadJob\n  max-points: %d\n  search-terms: %d\n  dry-run: %s\n  api-key: %s\n\n",
    $maxPoints,
    $searchTerms,
    $dryRun ? 'yes' : 'no',
    substr($apiKey, 0, 8) . '...'
);

$job = new \App\Services\Translation\RecipePreloadJob(
    $apiKey,
    $maxPoints,
    $searchTerms,
    $logger,
);

$result = $job->run($dryRun);

echo sprintf(
    "Resultado:\n  traducidas: %d\n  puntos usados: %d\n  motivo: %s\n  tiempo: %.2f seg\n",
    $result['translated'],
    $result['points_used'],
    $result['reason'],
    $result['elapsed_sec'] ?? 0,
);

$logger->log('info', '[preload-recipes] Finalizado', $result);
exit($result['translated'] > 0 || $result['reason'] === 'quota_exhausted' ? 0 : 1);
