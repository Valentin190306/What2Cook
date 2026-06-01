<?php
declare(strict_types=1);
 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
 
return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds'      => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'development',
        'development' => [
            'adapter' => 'pgsql',
            'host'    => $_ENV['DB_HOST'],
            'port'    => $_ENV['DB_PORT'],
            'name'    => $_ENV['DB_NAME'],
            'user'    => $_ENV['DB_USER'],
            'pass'    => $_ENV['DB_PASSWORD'],
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation',
];
