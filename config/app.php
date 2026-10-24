<?php
declare(strict_types=1);

// Application configuration.
//
// base_path is the URL prefix where the project is served:
//   http://localhost/clubf  => "/clubf"
//   https://example.com     => ""
//
// Create config/app.local.php (gitignored) to override values without editing
// this file. You can also set APP_BASE_PATH / ADMIN_INVITE_CODE env variables.
$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};

$config = [
    'app_name' => 'Kalaat Bona FC',
    'base_path' => $env('APP_BASE_PATH', '/clubf'),
    // Set a strong private code before deploying. When empty or "CHANGE_ME",
    // admin registration is only allowed while the admin table is empty.
    'admin_invite_code' => $env('ADMIN_INVITE_CODE', 'CHANGE_ME'),
];

$localConfig = __DIR__ . '/app.local.php';
if (is_file($localConfig)) {
    $override = require $localConfig;
    if (is_array($override)) {
        $config = array_merge($config, $override);
    }
}

return $config;
