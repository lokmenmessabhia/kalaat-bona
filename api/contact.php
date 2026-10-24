<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Méthode non autorisée'], 405);
}

$nom = trim((string)($_POST['nom_complet'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$telephone = trim((string)($_POST['telephone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($nom === '' || $email === '' || $telephone === '' || $message === '') {
    json_response(['error' => 'Veuillez remplir tous les champs obligatoires.'], 400);
}

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO contact_site (nom_complet, email, telephone, message) VALUES (?, ?, ?, ?)');
$stmt->execute([$nom, $email, $telephone, $message]);

json_response([
    'success' => true,
    'message' => 'Votre message a été envoyé avec succès ! Nous vous contacterons bientôt.'
]);
