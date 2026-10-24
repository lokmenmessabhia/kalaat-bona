<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/app.php';

$app = app_config();
$inviteCode = trim((string)($app['admin_invite_code'] ?? ''));
$isInviteConfigured = $inviteCode !== '' && $inviteCode !== 'CHANGE_ME';

// Block registration entirely if invite code is not configured
if (!$isInviteConfigured && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    // Only show the form if we're in dev mode and there are zero admins
    $adminCount = 0;
    try {
        $adminCount = (int)db()->query('SELECT COUNT(*) FROM admin')->fetchColumn();
    } catch (\Throwable $e) {}
    if ($adminCount > 0) {
        $error = 'L\'enregistrement d\'admins est désactivé. Configurez admin_invite_code dans config/app.php.';
    }
}

ensure_session_started();

$error = null;
$success = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    
    if (!$isInviteConfigured) {
        // Check if any admins exist — if so, block registration
        $adminCount = (int)db()->query('SELECT COUNT(*) FROM admin')->fetchColumn();
        if ($adminCount > 0) {
            $error = 'L\'enregistrement d\'admins est désactivé. Configurez admin_invite_code.';
        }
    }
    
    if (!$error) {
        $invite = trim((string)($_POST['invite_code'] ?? ''));
        $nom = trim((string)($_POST['nom'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!$isInviteConfigured) {
            // Dev mode: no invite check needed
        } elseif ($invite !== $inviteCode) {
            $error = 'Code invitation incorrect.';
        }
        
        if (!$error && ($nom === '' || $email === '' || $password === '')) {
            $error = 'Veuillez remplir tous les champs.';
        } elseif (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email invalide.';
        } elseif (!$error) {
            $email = mb_strtolower($email);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = db()->prepare('INSERT INTO admin (nom, email, mot_de_passe) VALUES (?, ?, ?)');
                $stmt->execute([$nom, $email, $hash]);
                $success = 'Admin créé avec succès. Vous pouvez vous connecter.';
            } catch (Throwable $e) {
                $error = 'Erreur: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Admin - Kalaat Bona FC</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #8B1538, #A91D3A, #C73650); min-height: 100vh; display:flex; align-items:center; justify-content:center; padding: 30px; }
        .card { width: 100%; max-width: 520px; background: rgba(255,255,255,0.96); border-radius: 20px; box-shadow: 0 25px 80px rgba(0,0,0,0.35); overflow:hidden; }
        .head { padding: 26px 26px 18px; background: linear-gradient(135deg, #8B1538, #C73650); color: #fff; }
        .head h1 { font-size: 1.7rem; margin-bottom: 6px; }
        .head p { color: #FFD700; font-weight: 700; }
        .body { padding: 22px 26px 26px; }
        .msg { padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; font-size: 0.95rem; }
        .msg.err { background: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); }
        .msg.ok { background: rgba(40, 167, 69, 0.1); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.2); }
        .row { margin-bottom: 14px; }
        label { display:block; font-weight: 800; color:#8B1538; margin-bottom: 6px; }
        input { width: 100%; padding: 12px 14px; border: 2px solid #e1e5e9; border-radius: 12px; font-size: 16px; }
        input:focus { outline:none; border-color:#C73650; box-shadow: 0 0 0 4px rgba(199,54,80,0.1); }
        button { width: 100%; margin-top: 6px; padding: 14px 16px; border: none; border-radius: 14px; background: linear-gradient(135deg, #8B1538, #C73650); color:#fff; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; cursor:pointer; }
        button:hover { filter: brightness(1.05); transform: translateY(-1px); }
        .links { margin-top: 14px; display:flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .links a { color:#C73650; font-weight: 800; text-decoration:none; }
        .links a:hover { text-decoration: underline; color:#8B1538; }
        .hint { margin-top: 10px; font-size: 0.9rem; color:#666; }
        code { background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="head">
            <h1>Créer un Admin</h1>
            <p>Protégé par code invitation</p>
        </div>
        <div class="body">
            <?php if ($error): ?>
                <div class="msg err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php elseif ($success): ?>
                <div class="msg ok"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="post">
                <?php csrf_field(); ?>
                <div class="row">
                    <label for="invite_code">Code invitation</label>
                    <input id="invite_code" name="invite_code" type="password" required>
                </div>
                <div class="row">
                    <label for="nom">Nom</label>
                    <input id="nom" name="nom" type="text" required>
                </div>
                <div class="row">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
                <div class="row">
                    <label for="password">Mot de passe</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button type="submit">Créer</button>
            </form>

            <div class="hint">
                <?php if ($isInviteConfigured): ?>
                    Le code invitation est requis pour créer un compte admin.
                <?php else: ?>
                    Mode développement : la vérification du code est désactivée.
                    <?php if (($adminCount ?? 0) > 0): ?>
                        <br><strong style="color:#dc3545;">L'enregistrement est bloqué car des admins existent déjà. Définissez admin_invite_code dans config/app.php.</strong>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="links">
                <a href="<?php echo htmlspecialchars(url_path('/admin/login.php'), ENT_QUOTES, 'UTF-8'); ?>">Connexion</a>
                <a href="<?php echo htmlspecialchars(url_path('/index.php'), ENT_QUOTES, 'UTF-8'); ?>">Retour site</a>
            </div>
        </div>
    </div>
</body>
</html>

