<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

require_admin();

$body = $_POST ?: get_json_body();
$id_joueur = (int)($body['id_joueur'] ?? 0);
$tailleRaw = trim((string)($body['taille'] ?? ''));
$poidsRaw = trim((string)($body['poids'] ?? ''));
$maladieChronique = trim((string)($body['maladie_chronique'] ?? ''));

if ($id_joueur <= 0) {
    json_response(['ok' => false, 'error' => 'ID invalide'], 422);
}

$taille = ($tailleRaw !== '' && is_numeric($tailleRaw)) ? (float)$tailleRaw : null;
$poids = ($poidsRaw !== '' && is_numeric($poidsRaw)) ? (float)$poidsRaw : null;

$pdo = db();
$pdo->prepare('UPDATE joueur SET taille = ?, poids = ?, maladie_chronique = ? WHERE id_joueur = ?')->execute([$taille, $poids, $maladieChronique ?: null, $id_joueur]);

json_response(['ok' => true]);
