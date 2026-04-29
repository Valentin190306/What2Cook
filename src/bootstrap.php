<?php

// Autoloader portátil
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once '/var/www/app/vendor/autoload.php';
}

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use App\Core\Database;

// ── 1. Whoops ────────────────────────────────────────────────────────────────
$whoops = new Run();
$whoops->pushHandler(new PrettyPageHandler());
$whoops->register();

// ── 2. Variables de entorno ──────────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);

// ── 3. Logger ────────────────────────────────────────────────────────────────
$logger = new Logger('what2cook');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../log/app.log', Logger::DEBUG));

// ── 4. Base de datos ─────────────────────────────────────────────────────────
try {
    $pdo = Database::getInstance();
    $logger->info('Conexión a la base de datos establecida');
} catch (Exception $e) {
    $logger->critical('Error de conexión a la base de datos', ['error' => $e->getMessage()]);
    die("Error crítico: No se pudo conectar a la base de datos.");
}