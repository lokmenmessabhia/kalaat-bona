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
if ($idJoueur <= 0) {
    json_response(['ok' => false, 'error' => 'ID joueur invalide.'], 422);
}

$pdo = db();

// Fetch all pending inscriptions for this player
$stmt = $pdo->prepare("
    SELECT i.*, j.prenom, j.nom AS joueur_nom, j.id_joueur, j.id_parent,
           p.nom AS parent_nom
    FROM inscription i
    JOIN joueur j ON j.id_joueur = i.id_joueur
    LEFT JOIN parent p ON p.id_parent = j.id_parent
    WHERE i.id_joueur = ? AND i.statut = 'en_attente'
");
$stmt->execute([$idJoueur]);
$inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($inscriptions)) {
    json_response(['ok' => false, 'error' => 'Aucune inscription en attente pour ce joueur.'], 404);
}

$first = $inscriptions[0];
$playerName = $first['prenom'] . ' ' . $first['joueur_nom'];

// Generate password at acceptance time (never stored in plaintext)
$anneeNaissance = '';
$birthStmt = $pdo->prepare("SELECT date_naissance FROM joueur WHERE id_joueur = ?");
$birthStmt->execute([$idJoueur]);
$birthDate = $birthStmt->fetchColumn();
if ($birthDate) {
    $anneeNaissance = (new DateTime($birthDate))->format('Y');
}

$isAdult = empty($first['id_parent']);
if ($isAdult) {
    $generatedPassword = strtolower(str_replace(' ', '', $first['prenom'] . $first['joueur_nom'])) . $anneeNaissance . $idJoueur;
    $hashJoueur = password_hash($generatedPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE joueur SET mot_de_passe = ? WHERE id_joueur = ?")->execute([$hashJoueur, $idJoueur]);
    $contactPass = $generatedPassword;
} else {
    $parentStmt = $pdo->prepare("SELECT nom FROM parent WHERE id_parent = ?");
    $parentStmt->execute([$first['id_parent']]);
    $parentNom = $parentStmt->fetchColumn() ?: '';
    $generatedPassword = strtolower(str_replace(' ', '', $parentNom)) . $anneeNaissance . $idJoueur;
    $hashParent = password_hash($generatedPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE parent SET mot_de_passe = ? WHERE id_parent = ?")->execute([$hashParent, $first['id_parent']]);
    $contactPass = $generatedPassword;
}

// Generate a one-time login token for direct portal access
$loginToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $loginToken);

$isAdult = empty($first['id_parent']);
if ($isAdult) {
    $pdo->prepare("UPDATE joueur SET login_token = ? WHERE id_joueur = ?")->execute([$tokenHash, $idJoueur]);
    $directLoginUrl = absolute_url('/portal_login?token=' . $loginToken . '&type=joueur');
} else {
    $pdo->prepare("UPDATE parent SET login_token = ? WHERE id_parent = ?")->execute([$tokenHash, $first['id_parent']]);
    $directLoginUrl = absolute_url('/portal_login?token=' . $loginToken . '&type=parent');
}

$emailSent = mailer_send(
    $first['email_contact'],
    'Félicitations ! Votre inscription a été acceptée',
    mailer_acceptance_html(
        $first['nom_contact'],
        $first['login_contact'],
        $contactPass,
        $playerName,
        (string)$idJoueur,
        $directLoginUrl
    )
);

// Accept all pending inscriptions for this player
$stmtUpdate = $pdo->prepare("UPDATE inscription SET statut='acceptee', traite_par=?, traite_le=NOW() WHERE id_joueur=? AND statut='en_attente'");
$stmtUpdate->execute([$admin['id_admin'], $idJoueur]);

if ($emailSent) {
    json_response(['ok' => true, 'message' => count($inscriptions) . ' inscription(s) acceptée(s) et email envoyé.']);
} else {
    json_response(['ok' => true, 'warning' => 'Inscription(s) acceptée(s), mais l\'email n\'a pas pu être envoyé (vérifiez la config SMTP).']);
}
