<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$s = get_settings();

session_start();
if (!isset($_SESSION['joueur_id'])) {
    header('Location: ' . url_path('/portal_login?type=joueur'));
    exit;
}

$id_joueur = (int)$_SESSION['joueur_id'];
$pdo = db();
$error = null;
$success = null;
$requestSent = false;

$pdo->exec("CREATE TABLE IF NOT EXISTS demande_changement (
    id_demande INT AUTO_INCREMENT PRIMARY KEY,
    id_joueur INT NOT NULL,
    poste_actuel VARCHAR(50) DEFAULT NULL,
    poste_souhaite VARCHAR(100) NOT NULL,
    motif TEXT DEFAULT NULL,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(20) DEFAULT 'en_attente',
    FOREIGN KEY (id_joueur) REFERENCES joueur(id_joueur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $adresse = trim((string)($_POST['adresse'] ?? ''));
        $nationalite = trim((string)($_POST['nationalite'] ?? ''));
        $telephone = str_replace(' ', '', trim((string)($_POST['telephone'] ?? '')));
        $email = trim((string)($_POST['email'] ?? ''));
        $experience = trim((string)($_POST['experience'] ?? ''));
        $clubPrecedent = trim((string)($_POST['club_precedent'] ?? ''));
        $maladieChronique = trim((string)($_POST['maladie_chronique'] ?? ''));
        $new_password = (string)($_POST['new_password'] ?? '');
        $photoPath = null;

        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
                $error = "Format de photo non accepté (jpg, png, webp uniquement).";
            } else {
                $filename = 'player_' . $id_joueur . '_' . time() . '.' . $ext;
                $dest = __DIR__ . '/uploads/' . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    $photoPath = $filename;
                }
            }
        }

        if (!$error) {
            try {
                $pdo->beginTransaction();
                $sql = 'UPDATE joueur SET adresse = ?, nationalite = ?, telephone = ?, email = ?, maladie_chronique = ?';
                $params = [$adresse, $nationalite, $telephone, $email, $maladieChronique ?: null];
                if ($photoPath) { $sql .= ', photo = ?'; $params[] = $photoPath; }
                $sql .= ' WHERE id_joueur = ?';
                $params[] = $id_joueur;
                $pdo->prepare($sql)->execute($params);

                $stmtPf = $pdo->prepare('SELECT id_profil FROM profil_football WHERE id_joueur = ?');
                $stmtPf->execute([$id_joueur]);
                $pfRow = $stmtPf->fetch();
                if ($pfRow) {
                    $pdo->prepare('UPDATE profil_football SET experience = ?, club_precedent = ? WHERE id_joueur = ?')
                        ->execute([$experience ?: null, $clubPrecedent ?: null, $id_joueur]);
                } else {
                    $pdo->prepare('INSERT INTO profil_football (id_joueur, experience, club_precedent) VALUES (?, ?, ?)')
                        ->execute([$id_joueur, $experience ?: null, $clubPrecedent ?: null]);
                }

                if ($new_password !== '') {
                    $hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $pdo->prepare('UPDATE joueur SET mot_de_passe = ? WHERE id_joueur = ?')->execute([$hash, $id_joueur]);
                }
                $pdo->commit();
                $success = "Profil mis à jour avec succès.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }

    if ($action === 'request_change') {
        $posteSouhaite = trim((string)($_POST['poste_souhaite'] ?? ''));
        $motif = trim((string)($_POST['motif'] ?? ''));
        if ($posteSouhaite === '') {
            $error = "Veuillez indiquer le poste souhaité.";
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO demande_changement (id_joueur, poste_actuel, poste_souhaite, motif) VALUES (?, ?, ?, ?)');
                $stmt->execute([$id_joueur, $joueur['poste'] ?? null, $posteSouhaite, $motif ?: null]);
                $requestSent = true;
                $success = "Votre demande de changement a été envoyée à l'équipe pédagogique.";
            } catch (Exception $e) {
                $error = "Erreur lors de l'envoi de la demande : " . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT j.*, pf.poste, pf.niveau, pf.experience, pf.club_precedent FROM joueur j LEFT JOIN profil_football pf ON j.id_joueur = pf.id_joueur WHERE j.id_joueur = ?');
$stmt->execute([$id_joueur]);
$joueur = $stmt->fetch();
$age = $joueur['date_naissance'] ? (int)(new DateTime())->diff(new DateTime($joueur['date_naissance']))->y : null;

// Player programmes
$progStmt = $pdo->prepare('SELECT p.nom_programme, i.statut, i.date_inscription FROM inscription i JOIN programme p ON i.id_programme = p.id_programme WHERE i.id_joueur = ? ORDER BY i.date_inscription DESC');
$progStmt->execute([$id_joueur]);
$programmes = $progStmt->fetchAll();

// Parent info
$parent = null;
if ($joueur['id_parent']) {
    $parStmt = $pdo->prepare('SELECT nom, telephone, email FROM parent WHERE id_parent = ?');
    $parStmt->execute([$joueur['id_parent']]);
    $parent = $parStmt->fetch();
}

$page_title = 'Mon Profil — ' . htmlspecialchars((string)$s['site_name']);
$hide_footer = true;
$extra_styles = <<<CSS
.profile-wrap { max-width:960px; margin:0 auto; padding:24px; }

.back-bar { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.back-bar a { display:flex; align-items:center; gap:6px; color:var(--c-text-muted); text-decoration:none; font-weight:600; font-size:0.9rem; transition:color var(--t-base); }
.back-bar a:hover { color:var(--c-navy); }

.profile-grid { display:grid; grid-template-columns:280px 1fr; gap:24px; }
@media(max-width:750px){ .profile-grid{grid-template-columns:1fr;} }

.p-card { background:white; border:1px solid var(--c-border); border-radius:var(--r-lg); box-shadow:var(--shadow-card); overflow:hidden; }
.p-card-head { padding:14px 20px; border-bottom:1px solid var(--c-border); display:flex; align-items:center; gap:8px; }
.p-card-head i { color:var(--c-sky); font-size:1rem; }
.p-card-head h3 { font-size:0.9rem; font-weight:700; color:var(--c-navy); }
.p-card-body { padding:20px; }

.photo-section { text-align:center; padding:28px 20px 20px; }
.photo-section .ps-img { width:100px; height:100px; border-radius:50%; object-fit:cover; border:4px solid var(--c-sky); margin:0 auto 12px; background:var(--c-sky-pale); display:flex; align-items:center; justify-content:center; font-size:2.5rem; color:var(--c-sky); }
.photo-section .ps-name { font-size:1.2rem; font-weight:800; color:var(--c-navy); }
.photo-section .ps-role { font-size:0.8rem; color:var(--c-text-muted); margin:2px 0 14px; }
.photo-upload { position:relative; display:inline-block; }
.photo-upload input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.photo-upload label { display:inline-flex; align-items:center; gap:6px; padding:7px 18px; border-radius:var(--r-md); font-weight:700; font-size:0.82rem; background:var(--c-navy); color:white; cursor:pointer; transition:all var(--t-base); }
.photo-upload label:hover { background:var(--c-navy-soft); }

.info-row { display:flex; justify-content:space-between; padding:8px 0; font-size:0.88rem; }
.info-row + .info-row { border-top:1px solid var(--c-border); }
.info-row .label { color:var(--c-text-muted); }
.info-row .value { font-weight:700; color:var(--c-navy); text-align:right; }



.form-group { margin-bottom:16px; }
.form-group label { display:block; font-weight:700; font-size:0.82rem; color:var(--c-navy); margin-bottom:5px; }
.form-control { width:100%; padding:10px 14px; border:2px solid var(--c-border); border-radius:var(--r-md); font-size:0.88rem; outline:none; transition:border-color var(--t-base); background:white; color:var(--c-text); font-family:inherit; }
.form-control:focus { border-color:var(--c-sky); }
textarea.form-control { resize:vertical; }

.btn-save { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:12px; border:none; border-radius:var(--r-md); background:var(--c-navy); color:white; font-weight:800; font-size:0.9rem; cursor:pointer; transition:all var(--t-base); }
.btn-save:hover { background:var(--c-navy-soft); transform:translateY(-1px); }

.alert { padding:12px 16px; border-radius:var(--r-md); margin-bottom:16px; font-weight:600; font-size:0.85rem; display:flex; align-items:center; gap:8px; }
.alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
.alert-error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

/* Modal */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.open { display:flex; }
.modal-box { background:white; border-radius:var(--r-xl); max-width:480px; width:100%; box-shadow:0 24px 64px rgba(0,0,0,0.2); overflow:hidden; animation:modalIn .25s ease; }
@keyframes modalIn { from{opacity:0;transform:scale(.95) translateY(10px)} to{opacity:1;transform:scale(1) translateY(0)} }
.modal-head { padding:18px 22px; border-bottom:1px solid var(--c-border); display:flex; align-items:center; justify-content:space-between; }
.modal-head h3 { font-size:1rem; font-weight:800; color:var(--c-navy); }
.modal-close { background:none; border:none; font-size:1.3rem; color:var(--c-text-muted); cursor:pointer; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; transition:background .2s; }
.modal-close:hover { background:var(--c-border); }
.modal-body { padding:22px; }

.btn-request { display:flex; align-items:center; justify-content:center; gap:6px; width:calc(100% - 40px); margin:0 20px 16px; padding:10px; border-radius:var(--r-md); border:1px solid var(--c-sky); background:transparent; color:var(--c-sky); font-weight:700; font-size:0.82rem; cursor:pointer; transition:all var(--t-base); }
.btn-request:hover { background:var(--c-sky); color:white; transform:translateY(-1px); }

body.dark .p-card { background:var(--c-navy-soft); border-color:#1e3a5a; }
body.dark .p-card-head h3 { color:var(--c-sky); }
body.dark .form-control { background:var(--c-navy-soft); border-color:#1e3a5a; color:var(--c-sky-light); }
body.dark .info-row .value { color:var(--c-sky); }

body.dark .modal-box { background:var(--c-navy-soft); }
body.dark .modal-head h3 { color:var(--c-sky); }
CSS;

require_once __DIR__ . '/lib/header.php';
?>
<div class="profile-wrap">
    <div class="back-bar">
        <a href="player_dashboard"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="back_to_dashboard">Tableau de bord</span></a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Left: Photo + Info -->
        <div>
            <div class="p-card" style="margin-bottom:16px;">
                <div class="photo-section">
                    <?php if(!empty($joueur['photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($joueur['photo']); ?>" class="ps-img" alt="">
                    <?php else: ?>
                        <div class="ps-img"><i class="fa-solid fa-futbol"></i></div>
                    <?php endif; ?>
                    <div class="ps-name"><?php echo htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']); ?></div>
                    <div class="ps-role"><?php echo htmlspecialchars($joueur['poste'] ?? 'Joueur'); ?> &middot; <?php echo htmlspecialchars($joueur['niveau'] ?? 'Club'); ?></div>
                </div>
            </div>

            <div class="p-card">
                <div class="p-card-head"><i class="fa-solid fa-info-circle"></i><h3 data-i18n="informations">Informations</h3></div>
                <div class="p-card-body">
                    <div class="info-row"><span class="label" data-i18n="player_id">ID Joueur</span><span class="value">#<?php echo $id_joueur; ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="first_name">Prénom</span><span class="value"><?php echo htmlspecialchars($joueur['prenom']); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="last_name">Nom</span><span class="value"><?php echo htmlspecialchars($joueur['nom']); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="gender">Sexe</span><span class="value"><?php echo htmlspecialchars($joueur['sexe'] ?? '—'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="info_label_birth_date">Date naissance</span><span class="value"><?php echo date('d/m/Y', strtotime($joueur['date_naissance'])); ?> <?php echo $age ? "($age ans)" : ''; ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="position">Poste</span><span class="value"><?php echo htmlspecialchars($joueur['poste'] ?? 'Non défini'); ?></span></div>
                    <button class="btn-request" onclick="openRequestModal()"><i class="fa-solid fa-arrows-rotate"></i> <span data-i18n="request_change">Demander un changement de poste</span></button>
                    <div class="info-row"><span class="label" data-i18n="level">Niveau</span><span class="value"><?php echo htmlspecialchars($joueur['niveau'] ?? 'Club'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="phone_label">Téléphone</span><span class="value"><?php echo htmlspecialchars($joueur['telephone'] ?? '—'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="email">Email</span><span class="value"><?php echo htmlspecialchars($joueur['email'] ?? '—'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="nationality_label">Nationalité</span><span class="value"><?php echo htmlspecialchars($joueur['nationalite'] ?? '—'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="address">Adresse</span><span class="value"><?php echo htmlspecialchars($joueur['adresse'] ?? '—'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="experience_label">Expérience</span><span class="value"><?php echo htmlspecialchars($joueur['experience'] ?? 'Non renseigné'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="previous_club_label">Club précédent</span><span class="value"><?php echo htmlspecialchars($joueur['club_precedent'] ?? 'Non renseigné'); ?></span></div>
                    <?php if(!empty($joueur['maladie_chronique'])): ?>
                    <div class="info-row" style="background:rgba(239,68,68,0.04);border:1px solid rgba(239,68,68,0.15);border-radius:8px;margin-top:8px;padding:8px 12px;">
                        <span class="label" style="color:#991b1b;"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px;"></i><span data-i18n="info_label_chronic_disease">Maladie chronique</span></span>
                        <span class="value" style="color:#991b1b;"><?php echo htmlspecialchars($joueur['maladie_chronique']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($parent): ?>
            <div class="p-card" style="margin-top:16px;">
                <div class="p-card-head"><i class="fa-solid fa-users"></i><h3 data-i18n="parent_guardian">Parent / Tuteur</h3></div>
                <div class="p-card-body">
                    <div class="info-row"><span class="label" data-i18n="last_name">Nom</span><span class="value"><?php echo htmlspecialchars($parent['nom']); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="phone_label">Téléphone</span><span class="value"><?php echo htmlspecialchars($parent['telephone'] ?? '—'); ?></span></div>
                    <div class="info-row"><span class="label" data-i18n="email">Email</span><span class="value"><?php echo htmlspecialchars($parent['email'] ?? '—'); ?></span></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($programmes): ?>
            <div class="p-card" style="margin-top:16px;">
                <div class="p-card-head"><i class="fa-solid fa-graduation-cap"></i><h3 data-i18n="side_programmes">Programmes</h3></div>
                <div class="p-card-body">
                    <?php foreach ($programmes as $p): ?>
                    <div class="info-row">
                        <span class="label"><?php echo htmlspecialchars($p['nom_programme']); ?></span>
                        <span class="value">
                            <?php if ($p['statut'] === 'acceptee'): ?>
                                <span style="color:#065f46;" data-i18n="accepted">Accepté</span>
                            <?php elseif ($p['statut'] === 'refusee'): ?>
                                <span style="color:#991b1b;" data-i18n="rejected">Refusé</span>
                            <?php else: ?>
                                <span style="color:#92400e;" data-i18n="badge_pending">En attente</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($programmes) > 0): ?>
                    <div class="info-row" style="border-top:1px dashed var(--c-border);margin-top:8px;padding-top:8px;">
                        <span class="label" data-i18n="registered_on">Inscrit le</span>
                        <span class="value"><?php echo date('d/m/Y', strtotime($programmes[0]['date_inscription'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Edit Form -->
        <div>
            <div class="p-card">
                <div class="p-card-head"><i class="fa-solid fa-user-gear"></i><h3 data-i18n="edit_profile">Modifier mon profil</h3></div>
                <div class="p-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label data-i18n="photo_label">Photo de profil</label>
                            <div class="photo-upload">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                                <label><i class="fa-solid fa-camera"></i> <span data-i18n="choose_photo">Choisir une photo</span></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label data-i18n="nationality_label">Nationalité</label>
                            <input type="text" name="nationalite" class="form-control" data-i18n-placeholder="nationality_label" placeholder="Nationalité" value="<?php echo htmlspecialchars($joueur['nationalite'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label data-i18n="phone_label">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" data-i18n-placeholder="phone_label" placeholder="Téléphone" value="<?php echo htmlspecialchars($joueur['telephone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label data-i18n="email">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($joueur['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label data-i18n="address_full">Adresse complète</label>
                            <textarea name="adresse" class="form-control" rows="2" data-i18n-placeholder="your_address" placeholder="Votre adresse"><?php echo htmlspecialchars($joueur['adresse'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label data-i18n="experience_label">Expérience</label>
                            <select name="experience" class="form-control">
                                <option value="" data-i18n="experience_not_set">— Non renseigné —</option>
                                <option value="0-1" data-i18n="experience_0_1" <?php echo ($joueur['experience'] ?? '') === '0-1' ? 'selected' : ''; ?>>0-1 an</option>
                                <option value="2-3" data-i18n="experience_2_3" <?php echo ($joueur['experience'] ?? '') === '2-3' ? 'selected' : ''; ?>>2-3 ans</option>
                                <option value="4-5" data-i18n="experience_4_5" <?php echo ($joueur['experience'] ?? '') === '4-5' ? 'selected' : ''; ?>>4-5 ans</option>
                                <option value="6+" data-i18n="experience_6_plus" <?php echo ($joueur['experience'] ?? '') === '6+' ? 'selected' : ''; ?>>6+ ans</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label data-i18n="previous_club_label">Club précédent</label>
                            <input type="text" name="club_precedent" class="form-control" data-i18n-placeholder="previous_club_label" placeholder="Club précédent" value="<?php echo htmlspecialchars($joueur['club_precedent'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label data-i18n="chronic_disease_allergies">Maladie chronique / Allergies</label>
                            <textarea name="maladie_chronique" class="form-control" rows="2" data-i18n-placeholder="chronic_disease_placeholder" placeholder="Ex: Asthme, Diabète, Allergie alimentaire..."><?php echo htmlspecialchars($joueur['maladie_chronique'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label data-i18n="new_password">Nouveau mot de passe</label>
                            <span style="font-weight:400;color:var(--c-text-muted);" data-i18n="keep_current">(laisser vide pour conserver l'actuel)</span>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn-save"><i class="fa-solid fa-check"></i> <span data-i18n="save">Enregistrer</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Change Modal -->
<div class="modal-overlay" id="requestModal">
    <div class="modal-box">
        <div class="modal-head">
            <h3><i class="fa-solid fa-arrows-rotate" style="color:var(--c-sky);margin-right:8px;"></i><span data-i18n="change_request_title">Demander un changement</span></h3>
            <button class="modal-close" onclick="closeRequestModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:0.85rem;color:var(--c-text-muted);margin-bottom:16px;" data-i18n="change_request_desc">Souhaitez-vous changer de poste ou de niveau ? Votre demande sera examinée par l'équipe pédagogique.</p>
            <form method="POST" id="requestForm">
                <input type="hidden" name="action" value="request_change">
                <div class="form-group">
                    <label data-i18n="current_position">Poste actuel</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($joueur['poste'] ?? 'Non défini'); ?>" readonly style="opacity:0.6;">
                </div>
                <div class="form-group">
                    <label data-i18n="desired_position">Poste souhaité <span class="required">*</span></label>
                    <select name="poste_souhaite" class="form-control" required>
                        <option value="" data-i18n="select_option">— Sélectionner —</option>
                        <?php foreach(['Gardien','Défenseur','Milieu','Attaquant','Latéral','Libéro'] as $p): ?>
                            <option value="<?php echo $p; ?>"><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label data-i18n="reason_optional">Motif (optionnel)</label>
                    <textarea name="motif" class="form-control" rows="3" data-i18n-placeholder="reason_placeholder_long" placeholder="Expliquez brièvement votre demande..."></textarea>
                </div>
                <button type="submit" class="btn-save" id="requestSubmit"><i class="fa-solid fa-paper-plane"></i> <span data-i18n="send_request">Envoyer la demande</span></button>
            </form>
        </div>
    </div>
</div>

<script>
function openRequestModal() { document.getElementById('requestModal').classList.add('open'); }
function closeRequestModal() { document.getElementById('requestModal').classList.remove('open'); }
document.getElementById('requestModal').addEventListener('click', function(e) { if (e.target === this) closeRequestModal(); });
<?php if ($requestSent): ?>
document.addEventListener('DOMContentLoaded', function() {
    const m = document.querySelector('.modal-overlay');
    if (m) m.classList.remove('open');
});
<?php endif; ?>
</script>
<?php require_once __DIR__ . '/lib/footer.php'; ?>
