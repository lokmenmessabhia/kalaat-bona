<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/app.php';

require_admin();
$admin = admin_current();
$role = $admin['role'] ?? 'subadmin';

$pdo = db();
$settings = get_settings();
$success = null;
$error = null;
$uploadDir = __DIR__ . '/../uploads/admins/';
$uploadUrl = url_path('/uploads/admins');

$me = $pdo->prepare('SELECT id_admin, nom, email, telephone, photo FROM admin WHERE id_admin = ?');
$me->execute([$admin['id_admin']]);
$me = $me->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nom = trim((string)($_POST['nom'] ?? ''));
    $telephone = trim((string)($_POST['telephone'] ?? ''));
    $new_pass = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $remove_photo = isset($_POST['remove_photo']);

    if ($nom === '') {
        $error = "Le nom ne peut pas être vide.";
    } elseif ($new_pass !== '' && $new_pass !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            $photo = $me['photo'];

            if ($remove_photo) {
                if ($photo && file_exists($uploadDir . $photo)) {
                    unlink($uploadDir . $photo);
                }
                $photo = null;
            }

            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = "Format autorisé : jpg, jpeg, png, gif, webp.";
                } else {
                    $filename = 'admin_' . $admin['id_admin'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $dest = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                        if ($photo && $photo !== $filename && file_exists($uploadDir . $photo)) {
                            unlink($uploadDir . $photo);
                        }
                        $photo = $filename;
                    } else {
                        $error = "Erreur lors de l'upload de la photo.";
                    }
                }
            }

            if (!$error) {
                if ($new_pass !== '') {
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE admin SET nom = ?, telephone = ?, photo = ?, mot_de_passe = ? WHERE id_admin = ?')
                        ->execute([$nom, $telephone, $photo, $hash, $admin['id_admin']]);
                } else {
                    $pdo->prepare('UPDATE admin SET nom = ?, telephone = ?, photo = ? WHERE id_admin = ?')
                        ->execute([$nom, $telephone, $photo, $admin['id_admin']]);
                }
                $_SESSION['admin_nom'] = $nom;
                $me['nom'] = $nom;
                $me['telephone'] = $telephone;
                $me['photo'] = $photo;
                $success = "Profil mis à jour avec succès !";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

$roleLabel = match ($role) {
    'main' => 'Super Admin',
    'subadmin' => 'Admin',
    'coach' => 'Coach',
    'financier' => 'Financier',
    'coordinateur' => 'Coordinateur',
    default => ucfirst($role),
};

$dashboardLinks = [
    'main' => '/admin/dashboard',
    'subadmin' => '/admin/dashboard',
    'coach' => '/admin/coach_dashboard',
    'financier' => '/admin/finance_dashboard',
    'coordinateur' => '/admin/coordinateur_dashboard',
];

$backUrl = url_path($dashboardLinks[$role] ?? '/admin/dashboard');

$admin_page_title = 'Mon Profil';
$admin_current_page = 'admin_profile';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .profile-layout { display: grid; grid-template-columns: 320px 1fr; gap: 28px; align-items: start; max-width: 1000px; }
    @media (max-width: 800px) { .profile-layout { grid-template-columns: 1fr; } }

    .card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
    body.dark .card { background: #0f1f3a; border-color: #1e3a5a; }

    /* ── Photo card (left) ── */
    .photo-card { text-align: center; padding: 36px 28px 28px; }
    .photo-card .avatar { width: 120px; height: 120px; border-radius: 50%; margin: 0 auto 18px; overflow: hidden; position: relative; background: var(--c-navy); display: flex; align-items: center; justify-content: center; font-size: 2.8rem; color: #fff; box-shadow: 0 8px 24px rgba(0,24,56,0.15); }
    .photo-card .avatar img { width: 100%; height: 100%; object-fit: cover; }
    .photo-card h2 { font-size: 1.2rem; font-weight: 800; color: var(--c-navy); margin: 0 0 2px; }
    .photo-card .role-badge { display: inline-block; padding: 4px 14px; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: rgba(108,173,223,0.1); color: var(--c-sky); border: 1px solid rgba(108,173,223,0.15); }
    body.dark .photo-card h2 { color: #f1f5f9; }

    .photo-upload-area { margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; gap: 10px; }
    body.dark .photo-upload-area { border-color: #1e3a5a; }
    .photo-upload-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; background: var(--c-sky); color: #fff; border-radius: 10px; font-weight: 700; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; border: none; }
    .photo-upload-btn:hover { opacity: 0.9; }
    .photo-delete-btn { padding: 9px 18px; background: transparent; color: #dc2626; border-radius: 10px; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; border: 1px solid rgba(220,38,38,0.2); }
    .photo-delete-btn:hover { background: #fef2f2; }
    body.dark .photo-delete-btn { border-color: rgba(220,38,38,0.3); }
    body.dark .photo-delete-btn:hover { background: rgba(220,38,38,0.1); }
    #photoInput { display: none; }
    .photo-preview { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--c-sky); display: none; }

    /* ── Form card (right) ── */
    .form-card { padding: 32px; }
    .form-card h3 { font-size: 1rem; font-weight: 800; color: var(--c-navy); margin: 0 0 20px; display: flex; align-items: center; gap: 8px; }
    body.dark .form-card h3 { color: #f1f5f9; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b; font-size: 0.85rem; }
    body.dark .form-group label { color: #cbd5e1; }
    .form-control { width: 100%; padding: 11px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; box-sizing:border-box; background: #fff; color: #1e293b; }
    .form-control:focus { outline: none; border-color: var(--c-sky); box-shadow: 0 0 0 3px rgba(108,173,223,0.1); }
    body.dark .form-control { background: #0a1e3a; border-color: #1e3a5a; color: #e2e8f0; }
    body.dark .form-control:focus { box-shadow: 0 0 0 3px rgba(108,173,223,0.08); }
    .form-control:read-only { opacity: 0.5; cursor: not-allowed; }

    .section-divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
    body.dark .section-divider { border-color: #1e3a5a; }

    .btn-primary { width: 100%; padding: 13px; background: var(--c-navy); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-primary:hover { opacity: 0.92; }

    .back-link { display: flex; align-items: center; gap: 6px; margin-top: 16px; color: #64748b; text-decoration: none; font-weight: 600; font-size: 0.88rem; justify-content: center; }
    .back-link:hover { color: var(--c-navy); }

    .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    body.dark .alert-success { background: rgba(5,150,105,0.1); border-color: rgba(5,150,105,0.2); }
    body.dark .alert-error { background: rgba(220,38,38,0.1); border-color: rgba(220,38,38,0.2); }

    .info-hint { color: #64748b; font-size: 0.82rem; margin-top: 4px; }
    body.dark .info-hint { color: #94a3b8; }
</style>

<div class="profile-layout">
    <?php if($success): ?>
        <div class="alert alert-success" style="grid-column:1/-1"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-error" style="grid-column:1/-1"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card photo-card">
        <div class="avatar" id="avatarDisplay">
            <?php if ($me['photo'] && file_exists($uploadDir . $me['photo'])): ?>
                <img src="<?php echo $uploadUrl . '/' . rawurlencode($me['photo']); ?>?v=<?php echo filemtime($uploadDir . $me['photo']); ?>" alt="Photo">
            <?php else: ?>
                <i class="fa-solid fa-user-tie"></i>
            <?php endif; ?>
        </div>
        <h2><?php echo htmlspecialchars($me['nom']); ?></h2>
        <span class="role-badge"><?php echo $roleLabel; ?></span>

        <div class="photo-upload-area">
            <img id="photoPreview" class="photo-preview" src="#" alt="Aperçu">
            <label for="photoInput" class="photo-upload-btn">
                <i class="fa-solid fa-camera"></i> Changer la photo
            </label>
            <?php if ($me['photo'] && file_exists($uploadDir . $me['photo'])): ?>
                <button type="submit" name="remove_photo" value="1" class="photo-delete-btn" form="profileForm" onclick="return confirm('Supprimer la photo ?')">
                    <i class="fa-solid fa-trash-can"></i> Supprimer
                </button>
            <?php endif; ?>
            <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/gif,image/webp">
            <span class="info-hint">JPG, PNG, WEBP — Max 40 Mo</span>
        </div>
    </div>

    <div class="card form-card">
        <h3><i class="fa-regular fa-pen-to-square" style="color:var(--c-sky);"></i> Modifier le profil</h3>

        <form method="POST" enctype="multipart/form-data" id="profileForm">
            <?php csrf_field(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($me['nom']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($me['email']); ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($me['telephone'] ?? ''); ?>" placeholder="+213 XXX XXX XXX">
            </div>

            <hr class="section-divider">

            <h3 style="margin-bottom:14px;"><i class="fa-solid fa-lock" style="color:var(--c-sky);"></i> Mot de passe</h3>
            <p class="info-hint" style="margin-bottom:16px;">Laisser vide pour conserver le mot de passe actuel.</p>
            <div class="form-row">
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Nouveau mot de passe" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Répéter le mot de passe" autocomplete="new-password">
                </div>
            </div>

            <hr class="section-divider">

            <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications</button>
        </form>

        <a href="<?php echo $backUrl; ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour au Dashboard
        </a>
    </div>
</div>

<script>
document.getElementById('photoInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        const preview = document.getElementById('photoPreview');
        const display = document.getElementById('avatarDisplay');
        preview.src = ev.target.result;
        preview.style.display = 'block';
        display.innerHTML = '<img src="' + ev.target.result + '" alt="Photo">';
    };
    reader.readAsDataURL(file);
});
</script>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
