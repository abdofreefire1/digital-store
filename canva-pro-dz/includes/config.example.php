<?php
declare(strict_types=1);

/*
 * Copy to config.php and set real values. Keep config.php out of Git.
 * Prefer environment variables on shared hosting when supported.
 */
return [
    'db' => [
        'host' => getenv('CPDZ_DB_HOST') ?: '127.0.0.1',
        'name' => getenv('CPDZ_DB_NAME') ?: 'canva_pro_dz',
        'user' => getenv('CPDZ_DB_USER') ?: '',
        'pass' => getenv('CPDZ_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'app_key' => getenv('CPDZ_APP_KEY') ?: '',
    'timezone' => 'Africa/Algiers',
    'proof_dir' => getenv('CPDZ_PROOF_DIR') ?: dirname(__DIR__) . '/private_uploads',
];
