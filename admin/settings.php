<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

require_admin();
$admin = admin_current();

// Only main admins can access settings
if (($admin['role'] ?? 'subadmin') !== 'main') {
    header('Location: ' . url_path('/admin/dashboard'));
    exit;
}

$pdo = db();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $site_name = trim((string)($_POST['site_name'] ?? ''));
    $primary_color = trim((string)($_POST['primary_color'] ?? '#8B1538'));
    $secondary_color = trim((string)($_POST['secondary_color'] ?? '#FFD700'));
    
    $smtp_host = trim((string)($_POST['smtp_host'] ?? ''));
    $smtp_port = trim((string)($_POST['smtp_port'] ?? ''));
    $smtp_user = trim((string)($_POST['smtp_user'] ?? ''));
    $smtp_pass = trim((string)($_POST['smtp_pass'] ?? ''));
    $smtp_encryption = trim((string)($_POST['smtp_encryption'] ?? ''));
    $mail_from = trim((string)($_POST['mail_from'] ?? ''));
    $mail_from_name = trim((string)($_POST['mail_from_name'] ?? ''));
    
    // Handle logo upload — secure version
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Size limit: 2MB
        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $error = "Le logo est trop volumineux (max 2 Mo).";
            return;
        }
        $tmp = $_FILES['logo']['tmp_name'];
        $name = basename($_FILES['logo']['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        // SVG excluded for security (XSS risk)
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            $error = "Format de logo non supporté (jpg, png, webp uniquement).";
            return;
        }
        // Validate MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            $error = "Le fichier logo n'est pas une image valide.";
            return;
        }
        $newName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($tmp, __DIR__ . '/../uploads/' . $newName)) {
            $logo_path = 'uploads/' . $newName;
        } else {
            $error = "Erreur lors de l'upload du logo.";
        }
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO settings (s_key, s_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE s_value = VALUES(s_value)");
        $stmt->execute(['site_name', $site_name]);
        $stmt->execute(['primary_color', $primary_color]);
        $stmt->execute(['secondary_color', $secondary_color]);
        
        if ($logo_path) {
            $stmt->execute(['site_logo', $logo_path]);
        }
        
        $stmt->execute(['smtp_host', $smtp_host]);
        $stmt->execute(['smtp_port', $smtp_port]);
        $stmt->execute(['smtp_user', $smtp_user]);
        if ($smtp_pass !== '') {
            $stmt->execute(['smtp_pass', $smtp_pass]);
        }
        $stmt->execute(['smtp_encryption', $smtp_encryption]);
        $stmt->execute(['mail_from', $mail_from]);
        $stmt->execute(['mail_from_name', $mail_from_name]);
        
        $pdo->commit();
        $success = "Paramètres mis à jour avec succès.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur : " . $e->getMessage();
    }
}

$settings = get_settings();

$admin_page_title = 'Paramètres du Site';
$admin_current_page = 'settings';
require_once __DIR__ . '/../lib/admin_header.php';
?>
<style>
    .card { background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 12px rgba(0,0,0,0.04); padding: 40px; border: 1px solid #e2e8f0; }
    .form-group { margin-bottom: 25px; }
    .form-label { display: block; font-weight: 700; color: var(--c-navy); margin-bottom: 8px; font-size: 0.9em; }
    .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 16px; transition: all 0.3s ease; background: #f8fafc; }
    .form-control:focus { outline: none; border-color: var(--c-sky); background: white; }
    .color-picker-group { display: flex; gap: 20px; }
    .color-picker-item { flex: 1; }
    .btn-save { background: var(--c-navy); color: white; border: none; padding: 15px 30px; border-radius: 12px; font-weight: 700; cursor: pointer; width: 100%; transition: all 0.3s ease; margin-top: 20px; box-shadow: 0 8px 20px rgba(0,24,56,0.15); }
    .btn-save:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,24,56,0.25); }
    .msg { padding: 15px; border-radius: 12px; margin-bottom: 25px; text-align: center; font-weight: 600; font-size: 0.95rem; }
    .msg-success { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
    .msg-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    .preview-logo { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 15px; }
</style>
        
        <div class="card">
            <h1 style="color:var(--primary-burgundy); margin-bottom: 35px; font-size: 1.8rem;">Configuration de l'Académie</h1>
            
            <?php if($success): ?>
                <div class="msg msg-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="msg msg-error"><i class="fa-solid fa-exclamation-triangle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label class="form-label">Nom de l'Académie</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars((string)$settings['site_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Logo actuel</label>
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <img src="../<?php echo htmlspecialchars((string)$settings['site_logo']); ?>" class="preview-logo" alt="Logo">
                        <input type="file" name="logo" class="form-control" accept="image/*" style="flex:1;">
                    </div>
                    <p style="font-size: 0.8em; color: #94a3b8; margin-top: 8px;">Recommandé : Image carrée 512x512px (PNG, JPG, SVG)</p>
                </div>

                <div class="color-picker-group">
                    <div class="color-picker-item">
                        <label class="form-label">Couleur Primaire (Burgundy)</label>
                        <input type="color" name="primary_color" class="form-control" value="<?php echo htmlspecialchars((string)$settings['primary_color']); ?>" style="height: 60px; padding: 5px;">
                    </div>
                    <div class="color-picker-item">
                        <label class="form-label">Couleur Secondaire (Gold)</label>
                        <input type="color" name="secondary_color" class="form-control" value="<?php echo htmlspecialchars((string)$settings['secondary_color']); ?>" style="height: 60px; padding: 5px;">
                    </div>
                </div>

                <hr style="border:none;border-top:2px solid #e2e8f0;margin:30px 0;">

                <h2 style="color:var(--c-navy);font-size:1.2rem;margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-envelope" style="color:var(--c-sky);"></i> Configuration Email (SMTP)
                </h2>
                <p style="color:#94a3b8;font-size:0.85rem;margin-bottom:20px;">
                    Utilisé pour envoyer les notifications d'acceptation / refus des inscriptions.
                    Pour Gmail, utilisez un <a href="https://support.google.com/accounts/answer/185833" target="_blank" style="color:var(--c-sky);">Mot de passe d'application</a>.
                </p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Hôte SMTP</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars((string)($settings['smtp_host'] ?? 'smtp.gmail.com')); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Port</label>
                        <input type="text" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars((string)($settings['smtp_port'] ?? '587')); ?>">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Email d'envoi</label>
                        <input type="email" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars((string)($settings['smtp_user'] ?? '')); ?>" placeholder="votre@gmail.com">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Mot de passe d'application</label>
                        <input type="password" name="smtp_pass" class="form-control" placeholder="•••••••• (laisser vide pour conserver)">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Sécurité</label>
                        <select name="smtp_encryption" class="form-control">
                            <option value="tls" <?php echo ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (recommended)</option>
                            <option value="ssl" <?php echo ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Email "De"</label>
                        <input type="email" name="mail_from" class="form-control" value="<?php echo htmlspecialchars((string)($settings['mail_from'] ?? '')); ?>" placeholder="noreply@kalaatbona.com">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Nom "De"</label>
                        <input type="text" name="mail_from_name" class="form-control" value="<?php echo htmlspecialchars((string)($settings['mail_from_name'] ?? '')); ?>" placeholder="Kallat Bona FC">
                    </div>
                </div>

                <button type="submit" class="btn-save">Enregistrer les modifications</button>
            </form>
                </div>
    </div>
<?php require_once __DIR__ . '/../lib/admin_footer.php'; ?>
