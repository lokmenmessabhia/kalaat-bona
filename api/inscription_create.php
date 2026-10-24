<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';

require_post();

// Use $_POST instead of JSON body to support file uploads
$body = $_POST;

// Check if POST body was discarded due to file size
if (empty($_POST) && empty($_FILES)) {
    json_response(['ok' => false, 'error' => 'Le fichier photo est trop volumineux. Maximum: 5 Mo. Réduisez la taille de l\'image ou choisissez-en une plus légère.'], 413);
}

// If the payload was JSON, $_POST might be empty, so fallback if needed
if (empty($_POST)) {
    $body = get_json_body();
}

$prenom = trim((string)($body['prenom'] ?? ''));
$nom = trim((string)($body['nom'] ?? ''));
$dateNaissance = trim((string)($body['dateNaissance'] ?? ''));
$sexe = trim((string)($body['sexe'] ?? ''));
$nationalite = trim((string)($body['nationalite'] ?? ''));
$adresse = trim((string)($body['adresse'] ?? ''));
$telephone = str_replace(' ', '', trim((string)($body['telephone'] ?? '')));
$emailJoueur = trim((string)($body['email'] ?? ''));

$telephoneParent = str_replace(' ', '', trim((string)($body['telephoneParent'] ?? '')));
$emailParent = trim((string)($body['emailParent'] ?? ''));
$nomParent = trim((string)($body['nomParent'] ?? ''));

$poste = trim((string)($body['poste'] ?? ''));
$niveau = trim((string)($body['niveau'] ?? ''));
$experience = trim((string)($body['experience'] ?? ''));
$clubPrecedent = trim((string)($body['clubPrecedent'] ?? ''));
$tailleRaw = trim((string)($body['taille'] ?? ''));
$poidsRaw = trim((string)($body['poids'] ?? ''));
$taille = ($tailleRaw !== '' && is_numeric($tailleRaw)) ? (float)$tailleRaw : null;
$poids = ($poidsRaw !== '' && is_numeric($poidsRaw)) ? (float)$poidsRaw : null;
$maladieChronique = trim((string)($body['maladie_chronique'] ?? ''));
$commentaires = trim((string)($body['commentaires'] ?? ''));
$programmes = $body['programmes'] ?? [];

// Age check
if ($dateNaissance === '') {
    json_response(['ok' => false, 'error' => 'DATE_BIRTH_REQUIRED'], 422);
}
$birthDate = new DateTime($dateNaissance);
$today = new DateTime();
$age = $today->diff($birthDate)->y;

$isAdult = ($age >= 18);

// Base Validation — tell exactly which field is missing
$missing = [];
if ($prenom === '') $missing[] = 'prénom';
if ($nom === '') $missing[] = 'nom';
if ($sexe === '') $missing[] = 'sexe';
if ($isAdult && $telephone === '') $missing[] = 'téléphone';
if (!empty($missing)) {
    json_response(['ok' => false, 'error' => 'Champs obligatoires manquants : ' . implode(', ', $missing)], 422);
}

if ($isAdult) {
    if ($emailJoueur === '') {
        json_response(['ok' => false, 'error' => 'EMAIL_REQUIRED_FOR_ADULTS'], 422);
    }
} else {
    if ($telephoneParent === '' || $emailParent === '' || $nomParent === '') {
        json_response(['ok' => false, 'error' => 'PARENT_INFO_REQUIRED_FOR_MINORS'], 422);
    }
}

// Temporary placeholder hash (will be replaced after getting the player ID)
$tempHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);

// programme IDs are submitted directly from the dynamic form
$programmeIds = array_values(array_unique(array_map('intval', (array)$programmes)));

// Photo upload — secure version
$photoDbPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Size limit: 5MB
    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
        json_response(['ok' => false, 'error' => 'PHOTO_TOO_LARGE'], 413);
    }
    $tmpName = $_FILES['photo']['tmp_name'];
    $name = basename($_FILES['photo']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    // Validate extension
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts)) {
        json_response(['ok' => false, 'error' => 'PHOTO_FORMAT_NOT_ALLOWED'], 422);
    }
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes)) {
        json_response(['ok' => false, 'error' => 'PHOTO_INVALID_MIME'], 422);
    }
    $uploadDir = __DIR__ . '/../uploads/';
    $newName = 'photo_' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
        $photoDbPath = $newName;
    } else {
        json_response(['ok' => false, 'error' => 'PHOTO_UPLOAD_FAILED'], 500);
    }
}

$pdo = db();
try {
    $pdo->beginTransaction();

    $idParent = null;
    $isExistingParent = false;
    if (!$isAdult) {
        $stmtParentLookup = $pdo->prepare('SELECT id_parent FROM parent WHERE telephone = ? OR email = ? LIMIT 1');
        $stmtParentLookup->execute([$telephoneParent, $emailParent]);
        $existingParent = $stmtParentLookup->fetch();
        if ($existingParent) {
            $idParent = (int)$existingParent['id_parent'];
            $isExistingParent = true;
        } else {
            $stmt = $pdo->prepare('INSERT INTO parent (nom, telephone, email, mot_de_passe) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nomParent, $telephoneParent, $emailParent, $tempHash]);
            $idParent = (int)$pdo->lastInsertId();
            $isExistingParent = false;
        }
    }
    
    // 2) joueur (now includes email, telephone, taille, poids, maladie_chronique)
    $stmt = $pdo->prepare('INSERT INTO joueur (prenom, nom, date_naissance, sexe, nationalite, adresse, id_parent, mot_de_passe, photo, telephone, email, taille, poids, maladie_chronique) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $finalPlayerEmail = $emailJoueur ?: null;
    $stmt->execute([
        $prenom, 
        $nom, 
        $dateNaissance, 
        strtolower($sexe), 
        $nationalite ?: null, 
        $adresse ?: null, 
        $idParent,
        $tempHash,
        $photoDbPath,
        $telephone,
        $finalPlayerEmail,
        $taille,
        $poids,
        $maladieChronique ?: null
    ]);
    $idJoueur = (int)$pdo->lastInsertId();
    
    // Passwords are generated at acceptance time (not stored in plaintext here)

    if ($isAdult) {
        $contactEmail  = $emailJoueur;
        $contactName   = $prenom . ' ' . $nom;
        $contactLogin  = $telephone;
    } elseif (!$isExistingParent) {
        $contactEmail  = $emailParent;
        $contactName   = $nomParent;
        $contactLogin  = $telephoneParent;
    } else {
        $contactEmail  = $emailParent;
        $contactName   = $nomParent;
        $contactLogin  = $telephoneParent;
    }

    // 4) profil_football
    $hasFootball = $poste !== '' || $niveau !== '' || $experience !== '' || $clubPrecedent !== '';
    if ($hasFootball) {
        $stmtPf = $pdo->prepare('INSERT INTO profil_football (id_joueur, poste, niveau, experience, club_precedent) VALUES (?, ?, ?, ?, ?)');
        $stmtPf->execute([$idJoueur, $poste ?: null, $niveau ?: null, $experience ?: null, $clubPrecedent ?: null]);
    }

    // 5) inscription
    if (!empty($programmeIds)) {
        $stmt = $pdo->prepare('INSERT INTO inscription (id_joueur, id_programme, commentaires, statut, email_contact, nom_contact, login_contact) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ($programmeIds as $idP) {
            $stmt->execute([$idJoueur, $idP, $commentaires ?: null, 'en_attente', $contactEmail, $contactName, $contactLogin]);
        }
    }

    $pdo->commit();
    json_response(['ok' => true, 'id_joueur' => $idJoueur], 201);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['ok' => false, 'error' => 'SERVER_ERROR', 'detail' => $e->getMessage()], 500);
}
