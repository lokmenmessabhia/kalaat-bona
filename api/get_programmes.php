<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method Not Allowed'], 405);
}

$pdo = db();

try {
    // Determine if the table has the rich columns (indicates we ran update_programmes.sql)
    $hasSrvCols = false;
    $cols = $pdo->query('SHOW COLUMNS FROM `programme`')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('icone', $cols)) {
        $hasSrvCols = true;
    }

    if ($hasSrvCols) {
        // Fetch all programmes with rich fields where icone is not null (filters out old basic ones if preferred, or returns all)
        // Let's return only the detailed entries we just inserted (ID > 4), or all. We'll return all, but prioritize rich ones.
        $stmt = $pdo->query('SELECT * FROM programme WHERE icone IS NOT NULL AND description != "" ORDER BY id_programme DESC LIMIT 10');
        $progs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback to minimal data if script hasn't been run yet
        $stmt = $pdo->query('SELECT * FROM programme');
        $progs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add fake minimal fields so frontend doesn't crash completely
        foreach ($progs as &$p) {
            $p['icone'] = 'fa-solid fa-futbol';
            $p['lieu'] = '-';
            $p['age'] = '-';
        }
    }

    json_response([
        'ok' => true,
        'data' => $progs
    ]);
} catch (PDOException $e) {
    json_response(['ok' => false, 'error' => 'Database error']);
}
