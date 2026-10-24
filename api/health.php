<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

try {
    $pdo = db();
    $pdo->query('SELECT 1')->fetch();
    json_response(['ok' => true, 'db' => 'connected']);
} catch (Throwable $e) {
    json_response(['ok' => false, 'db' => 'error', 'detail' => $e->getMessage()], 500);
}

