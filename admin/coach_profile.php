<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/app.php';

require_admin();
$admin = admin_current();
if (($admin['role'] ?? '') !== 'coach') {
    header('Location: ' . url_path('/admin/dashboard.php'));
    exit;
}

$pdo = db();
$settings = get_settings();
$success = null;
$error = null;

// Fetch full admin data with phone
$me = $pdo->prepare('SELECT id_admin, nom, email, telephone FROM admin WHERE id_admin = ?');
$me->execute([$admin['id_admin']]);
$me = $me->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string)($_POST['nom'] ?? ''));
    $telephone = trim((string)($_POST['telephone'] ?? ''));
    $new_pass = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($nom === '') {
        $error = "Le nom ne peut pas être vide.";
    } elseif ($new_pass !== '' && $new_pass !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        if ($new_pass !== '') {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admin SET nom = ?, telephone = ?, mot_de_passe = ? WHERE id_admin = ?')
                ->execute([$nom, $telephone, $hash, $admin['id_admin']]);
        } else {
            $pdo->prepare('UPDATE admin SET nom = ?, telephone = ? WHERE id_admin = ?')
                ->execute([$nom, $telephone, $admin['id_admin']]);
        }
        $_SESSION['admin_nom'] = $nom;
        $me['nom'] = $nom;
        $me['telephone'] = $telephone;
        $success = "Profil mis à jour avec succès !";
    }
}

$admin_page_title = 'Mon Profil';
$admin_current_page = 'coach_profile';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .profile-form-wrap { max-width: 600px; margin: 0 auto; }
    .profile-card { background: white; border-radius: 24px; padding: 50px; max-width: 520px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
    .profile-header { text-align: center; margin-bottom: 40px; }
    .avatar-icon { width: 90px; height: 90px; border-radius: 50%; background: var(--c-navy); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; }
    .profile-header h1 { font-size: 1.5rem; font-weight: 800; color: var(--c-navy); }
    .profile-header p { color: #64748b; font-size: 0.9rem; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 700; margin-bottom: 8px; color: #1e293b; font-size: 0.9rem; }
    .form-control { width: 100%; padding: 13px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; transition: border-color 0.3s; }
    .form-control:focus { outline: none; border-color: var(--c-sky); }
    .btn-save { width: 100%; padding: 15px; background: var(--c-navy); color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 1rem; cursor: pointer; transition: all 0.3s; margin-top: 10px; }
    .btn-save:hover { opacity: 0.9; transform: translateY(-2px); }
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 700; }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 700; }
    .back-link { display: block; text-align: center; margin-top: 20px; color: #64748b; text-decoration: none; font-weight: 600; }
    .back-link:hover { color: var(--c-navy); }
    .divider { border: none; border-top: 1px dashed #e2e8f0; margin: 25px 0; }
</style>
<div class="profile-form-wrap">
    <div class="profile-card">
        <div class="profile-header">
            <div class="avatar-icon"><i class="fa-solid fa-user-tie"></i></div>
            <h1><?php echo htmlspecialchars($me['nom']); ?></h1>
            <p><?php echo htmlspecialchars($me['email']); ?> &bull; <span style="color:var(--c-navy); font-weight:700;">Coach</span></p>
        </div>

        <?php if($success): ?>
            <div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label><i class="fa-solid fa-user" style="color:var(--c-navy); margin-right:6px;"></i> Nom complet</label>
                <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($me['nom']); ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-phone" style="color:var(--c-navy); margin-right:6px;"></i> Téléphone</label>
                <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($me['telephone'] ?? ''); ?>" placeholder="+213 XXX XXX XXX">
            </div>

            <hr class="divider">
            <p style="color:#64748b; font-size:0.85rem; margin-bottom:15px;"><i class="fa-solid fa-lock" style="margin-right:5px;"></i> Laisser vide pour ne pas changer le mot de passe</p>

            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="new_password" class="form-control" placeholder="Nouveau mot de passe">
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Répéter le mot de passe">
            </div>

            <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications</button>
        </form>

        <a href="<?php echo url_path('/admin/coach_dashboard.php'); ?>" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour au Dashboard
        </a>
    </div>
</div>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
