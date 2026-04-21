<?php

require_once '/var/www/app/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

// ── 1. Whoops ────────────────────────────────────────────────────────────────
$whoops = new Run();
$whoops->pushHandler(new PrettyPageHandler());
$whoops->register();

// ── 2. Variables de entorno ──────────────────────────────────────────────────
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);

// ── 3. Logger ────────────────────────────────────────────────────────────────
$logger = new Logger('pawprints');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../log/app.log', Logger::DEBUG));

// ── 4. Base de datos ─────────────────────────────────────────────────────────
try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME']
    );

    $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $logger->info('Conexión a la base de datos establecida');
} catch (PDOException $e) {
    $logger->critical('Error de conexión a la base de datos', ['error' => $e->getMessage()]);
    throw $e;
}