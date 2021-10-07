<?php

declare(strict_types=1);

global $osm_app; /* @var \Osm\Core\App $osm_app */

/* @see \Osm\Framework\Settings\Hints\Settings */
return (object)[
    'theme' => 'my',

    'db' => [
        'driver' => 'mysql',
        'url' => $_ENV['MYSQL_DATABASE_URL'] ?? null,
        'host' => $_ENV['MYSQL_HOST'] ?? 'localhost',
        'port' => $_ENV['MYSQL_PORT'] ?? '3306',
        'database' => "{$_ENV['MYSQL_DATABASE']}",
        'username' => $_ENV['MYSQL_USERNAME'],
        'password' => $_ENV['MYSQL_PASSWORD'],
        'unix_socket' => $_ENV['MYSQL_SOCKET'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
    'search' => [
        'driver' => 'elastic',
        'index_prefix' => $_ENV['SEARCH_INDEX_PREFIX'],
        'hosts' => [
            $_ENV['ELASTIC_HOST'] ?? 'localhost:9200',
        ],
        'retries' => 2,
    ],

    /* @see \Osm\Framework\Logs\Hints\LogSettings */
    'logs' => (object)[
        'elastic' => (bool)($_ENV['LOG_ELASTIC'] ?? false),
        'db' => (bool)($_ENV['LOG_DB'] ?? false),
    ],

    /* @see \Osm\Docs\Docs\Hints\Settings\Docs */
    'docs' => (object)[
        'index_modified' => true,
        'books' => [
            /* @see \Osm\Docs\Docs\Hints\Settings\Book */
            'framework' => (object)[
                'repo' => 'https://github.com/osmphp/framework.git',
                'path' => "{$osm_app->paths->temp}/docs/framework",
                'dir' => 'docs',
                'versions' => [
                    /* @see \Osm\Docs\Docs\Hints\Settings\Version */
                    '0.12' => (object)['branch' => 'v0.12'],
                    '0.13' => (object)['branch' => 'v0.13'],
                ],
            ],
        ],
    ],
];