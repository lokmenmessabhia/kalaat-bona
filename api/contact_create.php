<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

require_post();
$payload = get_json_body();

$nom_complet = trim((string)($payload['nom_complet'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$telephone = trim((string)($payload['telephone'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));

if ($nom_complet === '' || $email === '' || $telephone === '' || $message === '') {
    json_response(['ok' => false, 'error' => 'Veuillez remplir tous les champs.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'INVALID_EMAIL']);
}

$pdo = db();

try {
    $hasTable = $pdo->query("SHOW TABLES LIKE 'contact_site'")->fetch();
    if (!$hasTable) {
        json_response(['ok' => false, 'error' => 'TABLE_MISSING']);
    }
} catch (PDOException $e) {
    json_response(['ok' => false, 'error' => 'TABLE_MISSING']);
}

try {
    $stmt = $pdo->prepare('INSERT INTO contact_site (nom_complet, email, telephone, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$nom_complet, $email, $telephone, $message]);
    
    json_response(['ok' => true]);
} catch (PDOException $e) {
    json_response(['ok' => false, 'error' => 'Erreur lors de l\'enregistrement en base de données.']);
}
