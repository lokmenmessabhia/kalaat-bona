<?php
declare(strict_types=1);

function app_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }
    $cfg = require __DIR__ . '/../config/app.php';
    return is_array($cfg) ? $cfg : [];
}

function base_path(): string
{
    $cfg = app_config();
    $bp = (string)($cfg['base_path'] ?? '');
    $bp = rtrim($bp, '/');
    return $bp === '/' ? '' : $bp;
}

function url_path(string $path): string
{
    $path = '/' . ltrim($path, '/');
    // Automatique: remove .php for clean URLs
    if (str_ends_with(strtolower($path), '.php')) {
        $path = substr($path, 0, -4);
    }
    return base_path() . $path;
}

function absolute_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url_path($path);
}

