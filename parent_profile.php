<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/app.php';
require_once __DIR__ . '/lib/settings.php';
$s = get_settings();

session_start();
if (!isset($_SESSION['parent_id'])) {
    header('Location: ' . url_path('/portal_login?type=parent'));
    exit;
}

$id_parent = (int)$_SESSION['parent_id'];
$pdo = db();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim((string)($_POST['nom'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $telephone = trim((string)($_POST['telephone'] ?? ''));
    $new_password = (string)($_POST['new_password'] ?? '');
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE parent SET nom = ?, email = ?, telephone = ? WHERE id_parent = ?');
        $stmt->execute([$nom, $email, $telephone, $id_parent]);
        if ($new_password !== '') {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE parent SET mot_de_passe = ? WHERE id_parent = ?');
            $stmt->execute([$hash, $id_parent]);
        }
        $pdo->commit();
        $success = "Paramètres mis à jour avec succès.";
        $_SESSION['parent_nom'] = $nom;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Une erreur est survenue : " . $e->getMessage();
    }
}

$stmt = $pdo->prepare('SELECT * FROM parent WHERE id_parent = ?');
$stmt->execute([$id_parent]);
$parent = $stmt->fetch();

$page_title = 'Mon Profil — ' . htmlspecialchars((string)$s['site_name']);
$extra_styles = '
body { background: var(--bg-main); }
.profile-container { max-width: 700px; margin: 100px auto 40px; padding: 0 20px; }
.profile-card { background: var(--bg-card); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); overflow: hidden; }
.profile-header { background: var(--gradient-primary); color: white; padding: 40px; text-align: center; }
.profile-header h1 { font-size: 1.8rem; font-weight: 800; color: white; margin-bottom: 5px; }
.profile-body { padding: 40px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; color: var(--text-main); }
.alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; }
.alert-success { background: rgba(16,185,129,0.08); color: #10b981; border: 1px solid rgba(16,185,129,0.15); }
.alert-error { background: rgba(239,68,68,0.08); color: #ef4444; border: 1px solid rgba(239,68,68,0.15); }
';
$hide_footer = true;
require_once __DIR__ . '/lib/header.php';
?>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <h1 data-i18n="my_profile">Mon Profil Parent</h1>
            <p data-i18n="parent_id">Espace Parent</p>
        </div>
        <div class="profile-body">
            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label data-i18n="full_name">Nom complet</label>
                    <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($parent['nom'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="email_address">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($parent['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="phone">Téléphone</label>
                    <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($parent['telephone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary" style="width:100%; justify-content:center;">
                    <span data-i18n="save_changes">Enregistrer les modifications</span>
                    <i class="fa-solid fa-check"></i>
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="<?php echo url_path('/parent_dashboard'); ?>" style="color: var(--text-muted); text-decoration: none; font-weight: 600;">
                    <i class="fa-solid fa-arrow-left"></i> <span data-i18n="back_dashboard">Retour au tableau de bord</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/lib/footer.php'; ?>
