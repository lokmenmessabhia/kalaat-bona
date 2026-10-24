<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/app.php';

require_admin();
require_post();
verify_csrf();

$id_joueur = (int)($_POST['id_joueur'] ?? 0);
$presence = trim((string)($_POST['presence'] ?? 'Présent'));
$performance = trim((string)($_POST['performance'] ?? ''));
$remarque = trim((string)($_POST['remarque'] ?? ''));
$date_suivi = trim((string)($_POST['date_suivi'] ?? date('Y-m-d')));
$from = trim((string)($_POST['from'] ?? 'joueur'));
$program_id = (int)($_POST['program_id'] ?? 0);

if ($id_joueur <= 0 || $presence === '' || $performance === '') {
    if ($from === 'coach') {
        header('Location: ' . url_path('/admin/coach_dashboard.php?program_id='.$program_id.'&err=1'));
    } else {
        header('Location: ' . url_path('/admin/joueur_profil.php?id='.$id_joueur.'&err=1'));
    }
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO suivi (id_joueur, presence, performance, remarque, date_suivi) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$id_joueur, $presence, $performance, $remarque, $date_suivi]);

if ($from === 'coach') {
    header('Location: ' . url_path('/admin/coach_dashboard.php?program_id='.$program_id.'&ok=1'));
} else {
    header('Location: ' . url_path('/admin/joueur_profil.php?id='.$id_joueur.'&ok=1'));
}
exit;

