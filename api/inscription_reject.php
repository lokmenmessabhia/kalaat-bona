<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/mailer.php';

require_admin();
$admin = admin_current();

if (($admin['role'] ?? '') !== 'coordinateur' && ($admin['role'] ?? '') !== 'main') {
    json_response(['ok' => false, 'error' => 'Accès refusé.'], 403);
}

require_post();
verify_csrf();
$idJoueur = (int)($_POST['id_joueur'] ?? 0);
$motif = trim((string)($_POST['motif'] ?? ''));
if ($idJoueur <= 0) {
    json_response(['ok' => false, 'error' => 'ID joueur invalide.'], 422);
}

$pdo = db();

// Fetch all pending inscriptions for this player
$stmt = $pdo->prepare("
    SELECT i.*, j.prenom, j.nom AS joueur_nom
    FROM inscription i
    JOIN joueur j ON j.id_joueur = i.id_joueur
    WHERE i.id_joueur = ? AND i.statut = 'en_attente'
");
$stmt->execute([$idJoueur]);
$inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($inscriptions)) {
    json_response(['ok' => false, 'error' => 'Aucune inscription en attente pour ce joueur.'], 404);
}

$first = $inscriptions[0];
$playerName = $first['prenom'] . ' ' . $first['joueur_nom'];

$emailSent = mailer_send(
    $first['email_contact'],
    'Information concernant votre inscription',
    mailer_rejection_html($first['nom_contact'], $playerName, $motif)
);

// Reject all pending inscriptions for this player
$stmtUpdate = $pdo->prepare("UPDATE inscription SET statut='refusee', traite_par=?, traite_le=NOW() WHERE id_joueur=? AND statut='en_attente'");
$stmtUpdate->execute([$admin['id_admin'], $idJoueur]);

if ($emailSent) {
    json_response(['ok' => true, 'message' => count($inscriptions) . ' inscription(s) refusée(s) et email envoyé.']);
} else {
    json_response(['ok' => true, 'warning' => 'Inscription(s) refusée(s), mais l\'email n\'a pas pu être envoyé (vérifiez la config SMTP).']);
}
