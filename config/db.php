<?php
declare(strict_types=1);

// Database configuration.
//
// Defaults target a local XAMPP/WAMP installation. Override any value with
// environment variables, or create config/db.local.php (gitignored) returning
// an array with the keys you want to override.
$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

$config = [
    'host' => $env('DB_HOST', '127.0.0.1'),
    'port' => (int)$env('DB_PORT', '3306'),
    'name' => $env('DB_NAME', 'tilbi_academy'),
    'user' => $env('DB_USER', 'root'),
    'pass' => $env('DB_PASS', ''),
    'charset' => $env('DB_CHARSET', 'utf8mb4'),
];

$localConfig = __DIR__ . '/db.local.php';
if (is_file($localConfig)) {
    $override = require $localConfig;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}

return $config;
