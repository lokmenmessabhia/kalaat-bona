<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

require_admin();
verify_csrf();

$body = $_POST ?: get_json_body();
$id_joueur = (int)($body['id_joueur'] ?? 0);

if ($id_joueur <= 0) {
    json_response(['ok' => false, 'error' => 'ID invalide'], 422);
}

$pdo = db();

// Joueur fields
$prenom = trim((string)($body['prenom'] ?? ''));
$nom = trim((string)($body['nom'] ?? ''));
$sexe = trim((string)($body['sexe'] ?? ''));
$date_naissance = trim((string)($body['date_naissance'] ?? ''));
$nationalite = trim((string)($body['nationalite'] ?? ''));
$telephone = trim((string)($body['telephone'] ?? ''));
$email = trim((string)($body['email'] ?? ''));
$adresse = trim((string)($body['adresse'] ?? ''));
$tailleRaw = trim((string)($body['taille'] ?? ''));
$poidsRaw = trim((string)($body['poids'] ?? ''));
$maladieChronique = trim((string)($body['maladie_chronique'] ?? ''));

$taille = ($tailleRaw !== '' && is_numeric($tailleRaw)) ? (float)$tailleRaw : null;
$poids = ($poidsRaw !== '' && is_numeric($poidsRaw)) ? (float)$poidsRaw : null;

if ($prenom === '' || $nom === '') {
    json_response(['ok' => false, 'error' => 'Prénom et nom sont obligatoires.'], 422);
}

try {
    $pdo->prepare('UPDATE joueur SET prenom=?, nom=?, sexe=?, date_naissance=?, nationalite=?, telephone=?, email=?, adresse=?, taille=?, poids=?, maladie_chronique=? WHERE id_joueur=?')->execute([
        $prenom, $nom, $sexe, $date_naissance ?: null, $nationalite ?: null,
        $telephone, $email ?: null, $adresse ?: null,
        $taille, $poids, $maladieChronique ?: null, $id_joueur
    ]);

    // Profil football (poste excluded — requires player request + coach approval)
    $niveau = trim((string)($body['niveau'] ?? ''));
    $experience = trim((string)($body['experience'] ?? ''));
    $clubPrecedent = trim((string)($body['club_precedent'] ?? ''));

    $exists = $pdo->prepare('SELECT id_profil FROM profil_football WHERE id_joueur = ?');
    $exists->execute([$id_joueur]);
    $hasProfil = $exists->fetch();

    if ($hasProfil) {
        $pdo->prepare('UPDATE profil_football SET niveau=?, experience=?, club_precedent=? WHERE id_joueur=?')->execute([
            $niveau ?: null, $experience ?: null, $clubPrecedent ?: null, $id_joueur
        ]);
    } else {
        $pdo->prepare('INSERT INTO profil_football (id_joueur, niveau, experience, club_precedent) VALUES (?,?,?,?)')->execute([
            $id_joueur, $niveau ?: null, $experience ?: null, $clubPrecedent ?: null
        ]);
    }

    json_response(['ok' => true]);
} catch (\Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
