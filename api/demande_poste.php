<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

session_start();
if (!isset($_SESSION['joueur_id'])) {
    json_response(['ok' => false, 'error' => 'Non authentifié'], 401);
}

$id_joueur = (int)$_SESSION['joueur_id'];
$body = $_POST ?: get_json_body();
$posteDemande = trim((string)($body['poste_demande'] ?? ''));
$raison = trim((string)($body['raison'] ?? ''));

if ($posteDemande === '') {
    json_response(['ok' => false, 'error' => 'Choisissez un poste'], 422);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT pf.poste FROM profil_football pf WHERE pf.id_joueur = ?');
$stmt->execute([$id_joueur]);
$row = $stmt->fetch();
$posteActuel = $row ? ($row['poste'] ?? null) : null;

if ($posteDemande === $posteActuel) {
    json_response(['ok' => false, 'error' => 'C\'est déjà votre poste actuel'], 422);
}

$check = $pdo->prepare('SELECT id_demande FROM demande_changement WHERE id_joueur = ? AND statut = ?');
$check->execute([$id_joueur, 'en_attente']);
if ($check->fetch()) {
    json_response(['ok' => false, 'error' => 'Vous avez déjà une demande en attente']);
}

$pdo->prepare('INSERT INTO demande_changement (id_joueur, poste_actuel, poste_souhaite, motif) VALUES (?,?,?,?)')->execute([
    $id_joueur, $posteActuel, $posteDemande, $raison ?: null
]);

json_response(['ok' => true]);
