<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/app.php';
require_once __DIR__ . '/../lib/settings.php';

session_start();
if (admin_is_logged_in()) {
    header('Location: ' . url_path('/admin/dashboard'));
    exit;
}

$error = null;
$s = get_settings();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $email = (string)($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    
    $blocked = rate_limit_check('admin_login', $email);
    if ($blocked !== null) {
        $error = 'Trop de tentatives. Réessayez dans ' . ceil($blocked / 60) . ' minute(s).';
    } elseif (admin_login_by_credentials($email, $password)) {
        rate_limit_reset('admin_login', $email);
        $user = admin_current();
        $role = $user['role'] ?? '';
        if ($role === 'coach') {
            header('Location: ' . url_path('/admin/coach_dashboard.php'));
        } elseif ($role === 'financier') {
            header('Location: ' . url_path('/admin/finance_dashboard.php'));
        } elseif ($role === 'coordinateur') {
            header('Location: ' . url_path('/admin/coordinateur_dashboard.php'));
        } else {
            header('Location: ' . url_path('/admin/dashboard.php'));
        }
        exit;
    }
    rate_limit_record('admin_login', $email);
    $error = 'Email ou mot de passe incorrect.';
}


$logo = $s['site_logo'] ?: 'logo_kala.webp';
$name = $s['site_name'] ?: 'KALAAT BONA';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration | <?php echo htmlspecialchars((string)$name); ?></title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <?php render_dynamic_styles(); ?>
    <style>
        body {
            background: var(--c-navy, #001838);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(108,173,223,0.08) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 50%, rgba(108,173,223,0.05) 0%, transparent 60%);
            pointer-events: none;
        }

        .login-card {
            background: #fff;
            width: 100%;
            max-width: 420px;
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.4);
            overflow: hidden;
            position: relative;
            z-index: 10;
            animation: cardIn 0.6s ease-out;
        }

        .login-header {
            padding: 48px 40px 32px;
            text-align: center;
            background: #fff;
        }

        .login-logo-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--c-offwhite, #f7f8fa);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 3px solid var(--c-sky, #6caddf);
            overflow: hidden;
        }

        .login-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--c-navy, #001838);
            margin-bottom: 4px;
        }

        .login-header p {
            color: var(--c-text-muted, #64748b);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .login-body {
            padding: 0 40px 40px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--c-text, #1a2332);
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .form-control {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border: 2px solid var(--c-border, #e2e8f0);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: var(--c-offwhite, #f7f8fa);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--c-sky, #6caddf);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(108,173,223,0.1);
        }

        .form-icons {
            position: absolute;
            left: 16px;
            top: 39px;
            color: var(--c-text-muted, #64748b);
            font-size: 1.05rem;
        }

        .btn-login {
            background: var(--c-navy, #001838);
            color: #fff;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: var(--c-navy-soft, #0a2a4a);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(0,24,56,0.2);
        }

        .error-msg {
            background: #fef2f2;
            color: #dc2626;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fee2e2;
            font-size: 0.88rem;
            font-weight: 600;
            text-align: center;
        }

        .login-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--c-text-muted, #64748b);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 24px;
            transition: color 0.2s;
        }

        .login-back-link:hover {
            color: var(--c-navy, #001838);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-wrap">
                <img src="../<?php echo htmlspecialchars((string)$logo); ?>" alt="Logo">
            </div>
            <h1>Administration</h1>
            <p>Accès sécurisé Kalaat Bona FC</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-msg"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label>Identifiant Email</label>
                    <i class="fa-solid fa-user-shield form-icons"></i>
                    <input type="email" name="email" class="form-control" placeholder="admin@kalaatbona.com" required autofocus>
                </div>
                <div class="form-group">
                    <label>Mot de passe</label>
                    <i class="fa-solid fa-key form-icons"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-login">Se connecter</button>
            </form>

            <div style="text-align: center;">
                <a href="<?php echo url_path('/index'); ?>" class="login-back-link">
                    <i class="fa-solid fa-arrow-left"></i> Retour au site
                </a>
            </div>
        </div>
    </div>
</body>
</html>
