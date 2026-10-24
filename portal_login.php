<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/settings.php';

session_start();

$error = null;

// Direct login via one-time token from email
$token = $_GET['token'] ?? '';
if ($token !== '') {
    $tokenHash = hash('sha256', $token);
    $pdo = db();

    // Try player token
    $stmt = $pdo->prepare("SELECT id_joueur, prenom, nom FROM joueur WHERE login_token = ? LIMIT 1");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['joueur_id'] = (int)$user['id_joueur'];
        $_SESSION['joueur_nom'] = $user['prenom'] . ' ' . $user['nom'];
        $pdo->prepare("UPDATE joueur SET login_token = NULL WHERE id_joueur = ?")->execute([$user['id_joueur']]);
        header('Location: ' . url_path('/player_dashboard'));
        exit;
    }

    // Try parent token
    $stmt = $pdo->prepare("SELECT id_parent, nom FROM parent WHERE login_token = ? LIMIT 1");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['parent_id'] = (int)$user['id_parent'];
        $_SESSION['parent_nom'] = $user['nom'];
        $pdo->prepare("UPDATE parent SET login_token = NULL WHERE id_parent = ?")->execute([$user['id_parent']]);
        header('Location: ' . url_path('/parent_dashboard'));
        exit;
    }

    // Invalid/expired token — fall through to normal login with error
    $error = 'Lien invalide ou expiré. Veuillez vous connecter manuellement.';
}

$type = $_GET['type'] ?? 'joueur';
if (!in_array($type, ['joueur','parent'], true)) $type = 'joueur';
$s = get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifiant = trim((string)($_POST['identifiant'] ?? ''));
    $password = $_POST['motDePasse'] ?? '';
    $postType = $_POST['type'] ?? $type;
    $postType = in_array($postType, ['joueur','parent'], true) ? $postType : 'joueur';
    
    $blocked = rate_limit_check('portal_login', $identifiant);
    if ($blocked !== null) {
        $error = 'Trop de tentatives. Réessayez dans ' . ceil($blocked / 60) . ' minute(s).';
    } else {
        $pdo = db();
        $found = false;
        // Try requested type first, then fallback to other type (old behavior + ORDER BY fix)
        $tryOrder = $postType === 'parent' ? ['parent','joueur'] : ['joueur','parent'];
        foreach ($tryOrder as $try) {
            if ($try === 'parent') {
                $stmt = $pdo->prepare('SELECT id_parent, mot_de_passe, nom FROM parent WHERE telephone = ? OR email = ? ORDER BY id_parent DESC LIMIT 1');
                $stmt->execute([$identifiant, $identifiant]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, (string)$user['mot_de_passe'])) {
                    rate_limit_reset('portal_login', $identifiant);
                    $_SESSION['parent_id'] = (int)$user['id_parent'];
                    $_SESSION['parent_nom'] = $user['nom'];
                    header('Location: ' . url_path('/parent_dashboard'));
                    exit;
                }
            } else {
                $stmt = $pdo->prepare('SELECT id_joueur, mot_de_passe, prenom, nom FROM joueur WHERE email = ? OR telephone = ? ORDER BY id_joueur DESC LIMIT 1');
                $stmt->execute([$identifiant, $identifiant]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, (string)$user['mot_de_passe'])) {
                    $st = $pdo->prepare("SELECT statut FROM inscription WHERE id_joueur = ? ORDER BY id_inscription DESC LIMIT 1");
                    $st->execute([$user['id_joueur']]);
                    $statut = $st->fetchColumn();
                    if ($statut === 'en_attente') {
                        $error = 'Inscription en attente de validation par l\'admin. Vous recevrez un email à l\'acceptation.';
                        $found = true;
                        break;
                    } elseif ($statut === 'refusee') {
                        $error = 'Inscription refusée. Contactez l\'administration.';
                        $found = true;
                        break;
                    } else {
                        rate_limit_reset('portal_login', $identifiant);
                        $_SESSION['joueur_id'] = (int)$user['id_joueur'];
                        $_SESSION['joueur_nom'] = $user['prenom'] . ' ' . $user['nom'];
                        header('Location: ' . url_path('/player_dashboard'));
                        exit;
                    }
                }
            }
        }
        if ($error === null) {
            rate_limit_record('portal_login', $identifiant);
            $error = 'Identifiant ou mot de passe incorrect.';
        } elseif (!$found && $error !== null && !str_contains($error, 'en attente') && !str_contains($error, 'refusée')) {
            rate_limit_record('portal_login', $identifiant);
        }
    }
}

$page_title = 'Connexion — ' . htmlspecialchars((string)$s['site_name']);
$extra_styles = '
body { background: var(--c-offwhite); font-family: "Inter", sans-serif; min-height: 100vh; }
.login-wrapper { min-height: calc(100vh - var(--nav-h)); display: flex; align-items: center; justify-content: center; padding: 20px; }

.login-card {
    background: var(--c-white);
    width: 100%;
    max-width: 420px;
    border-radius: var(--r-lg);
    box-shadow: var(--shadow-hover);
    overflow: hidden;
    animation: cardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes cardIn { from { opacity: 0; transform: translateY(30px) scale(0.96); } to { opacity: 1; transform: translateY(0) scale(1); } }
.login-header {
    background: var(--c-navy);
    padding: var(--s8) var(--s5);
    text-align: center;
    position: relative;
    overflow: hidden;
    isolation: isolate;
}
.login-header::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,173,223,0.1), transparent);
}
.login-header::after {
    content: "";
    position: absolute;
    top: 50%; right: 0;
    width: 70%; height: 100%;
    background: url("logo_kala.webp") left center / cover no-repeat;
    transform: translateY(-50%);
    opacity: 0.08;
    filter: grayscale(1);
    pointer-events: none;
    mask: linear-gradient(to right, transparent 30%, #000 55%);
    -webkit-mask: linear-gradient(to right, transparent 30%, #000 55%);
}
.login-header h1 { font-size: clamp(1.4rem, 4vw, 1.8rem); font-weight: 800; margin-bottom: 6px; color: var(--c-white); position: relative; }
.login-header p { opacity: 0.8; font-size: clamp(0.85rem, 2vw, 0.95rem); font-weight: 500; position: relative; color: var(--c-white); }
.login-body { padding: var(--s6); }
.type-toggle {
    position: relative;
    display: flex;
    background: var(--c-offwhite);
    border-radius: var(--r-md);
    padding: 4px;
    margin-bottom: var(--s5);
    border: 1px solid var(--c-border);
}
.toggle-slider {
    position: absolute;
    top: 4px; left: 4px;
    width: calc(50% - 4px); height: calc(100% - 8px);
    background: var(--c-white);
    border-radius: calc(var(--r-md) - 3px);
    box-shadow: var(--shadow-card);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}
.toggle-slider.right { transform: translateX(100%); }
.toggle-btn {
    flex: 1;
    position: relative;
    z-index: 1;
    border: none;
    padding: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: color var(--t-fast);
    text-align: center;
    font-size: 0.85rem;
    font-family: inherit;
    background: transparent;
    color: var(--c-text-muted);
}
.toggle-btn.active { color: var(--c-navy); }
.toggle-btn:not(.active):hover { color: var(--c-text); }
.form-group { margin-bottom: var(--s5); }
.form-group label { display: block; font-weight: 700; color: var(--c-text); margin-bottom: 6px; font-size: 0.85rem; }
.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 14px; color: var(--c-text-muted); font-size: 0.9rem; pointer-events: none; z-index: 1; }
.form-control { width: 100%; padding: 12px 16px 12px 42px; border: 1px solid var(--c-border); border-radius: var(--r-md); font-size: 0.9rem; transition: all var(--t-fast); background: var(--c-offwhite); color: var(--c-text); outline: none; font-family: inherit; box-sizing: border-box; }
.form-control:focus { border-color: var(--c-sky); background: var(--c-white); box-shadow: 0 0 0 3px rgba(108,173,223,0.12); }
.password-toggle { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--c-text-muted); font-size: 1rem; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color var(--t-fast); z-index: 1; }
.password-toggle:hover { color: var(--c-text); }
.btn-login {
    background: linear-gradient(135deg, var(--c-sky), #4a90d9);
    color: var(--c-white);
    width: 100%;
    padding: 13px;
    border-radius: var(--r-full);
    border: none;
    font-weight: 700;
    cursor: pointer;
    margin-top: var(--s3);
    transition: all var(--t-base);
    box-shadow: 0 4px 14px rgba(74,144,217,0.3);
    font-family: inherit;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(74,144,217,0.4); }
.btn-login:active { transform: translateY(0); }
.error-msg { background: rgba(239, 68, 68, 0.08); color: #ef4444; padding: var(--s3) var(--s4); border-radius: var(--r-md); margin-bottom: var(--s5); border: 1px solid rgba(239, 68, 68, 0.15); font-size: 0.85rem; font-weight: 600; text-align: center; display: flex; align-items: center; gap: 8px; justify-content: center; }
.footer-links { text-align: center; margin-top: var(--s6); font-size: 0.9rem; color: var(--c-text-muted); display: flex; flex-direction: column; align-items: center; gap: 4px; }
.footer-links .join-link { color: #001838; text-decoration: none; font-weight: 700; transition: color var(--t-fast); }
.footer-links .join-link:hover { color: var(--c-sky); }
body.dark .login-card { border-color: var(--c-border); }
body.dark .login-header { background: var(--c-white); }
body.dark .login-header h1 { color: var(--c-text); }
body.dark .login-header p { color: var(--c-text-muted); }
body.dark .login-header::before { background: radial-gradient(ellipse at 50% 0%, rgba(108,173,223,0.05), transparent); }
body.dark .login-wrapper .main-content { background: var(--c-offwhite); }
body.dark .toggle-slider { background: var(--c-offwhite); }
body.dark .toggle-btn.active { color: var(--c-text); }
body.dark .toggle-btn:not(.active) { color: var(--c-text-muted); }
body.dark .form-control { background: var(--c-white); border-color: var(--c-border); color: var(--c-text); }
body.dark .form-control:focus { background: var(--c-offwhite); border-color: var(--c-sky); }
body.dark .type-toggle { background: var(--c-white); }
body.dark .input-icon { color: var(--c-text-muted); }
body.dark .password-toggle { color: var(--c-text-muted); }
body.dark .password-toggle:hover { color: var(--c-text); }
body.dark .footer-links .join-link { color: var(--c-sky); }
body.dark .footer-links .join-link:hover { color: var(--c-sky-light); }
body.dark .footer-links { color: var(--c-text-muted); }
#ball-anim { display:none; position:fixed; inset:0; z-index:99999; align-items:center; justify-content:center; }
#ballVideo { width:100vw; height:100vh; object-fit:cover; display:block; }
';
require_once __DIR__ . '/lib/header.php';
?>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-header">
            <h1 data-i18n="portal_title">Espace Kalaat Bona</h1>
            <p data-i18n="portal_subtitle">Bienvenue sur votre espace personnel</p>
        </div>
        <div class="login-body">
            <div class="type-toggle" id="typeToggle">
                <div class="toggle-slider<?php echo $type==='parent'?' right':''; ?>" id="toggleSlider"></div>
                <button type="button" class="toggle-btn<?php echo $type==='joueur'?' active':''; ?>" data-type="joueur"><i class="fa-solid fa-futbol"></i> <span data-i18n="player">Joueur</span></button>
                <button type="button" class="toggle-btn<?php echo $type==='parent'?' active':''; ?>" data-type="parent"><i class="fa-solid fa-users"></i> <span data-i18n="parent">Parent</span></button>
            </div>

            <?php if ($error): ?>
                <div class="error-msg"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <?php csrf_field(); ?>
                <input type="hidden" name="type" id="loginType" value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label data-i18n="login_label">Identifiant (Email ou Téléphone)</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user input-icon"></i>
                        <input type="text" name="identifiant" class="form-control" placeholder="email@exemple.com" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label data-i18n="password_label">Mot de passe</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" name="motDePasse" id="passwordInput" class="form-control" placeholder="••••••••" required style="padding-right: 42px;">
                        <button type="button" class="password-toggle" id="passwordToggle" tabindex="-1">
                            <i class="fa-solid fa-eye" id="passwordIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> <span data-i18n="login_btn">Se connecter</span></button>
            </form>

            <div class="footer-links">
                <span data-i18n="no_account">Pas encore inscrit ?</span>
                <a href="<?php echo url_path('/formulair'); ?>" class="join-link" data-i18n="join_now">Rejoignez-nous</a>
            </div>
        </div>
    </div>
</div>

<div id="ball-anim">
    <video id="ballVideo" muted loop autoplay playsinline preload="auto">
        <source src="<?php echo url_path('/ball.webm'); ?>" type="video/webm">
        <source src="<?php echo url_path('/ball.mp4'); ?>" type="video/mp4">
    </video>
</div>

<script>
// Type toggle sliding
const toggleBtns = document.querySelectorAll('#typeToggle .toggle-btn');
const toggleSlider = document.getElementById('toggleSlider');
const loginType = document.getElementById('loginType');
toggleBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        toggleBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        toggleSlider.classList.toggle('right', btn.dataset.type === 'parent');
        if (loginType) loginType.value = btn.dataset.type;
        const url = new URL(window.location);
        url.searchParams.set('type', btn.dataset.type);
        window.history.replaceState({}, '', url);
    });
});

// Password toggle
const passwordToggle = document.getElementById('passwordToggle');
const passwordInput = document.getElementById('passwordInput');
const passwordIcon = document.getElementById('passwordIcon');
if (passwordToggle && passwordInput) {
    passwordToggle.addEventListener('click', () => {
        const show = passwordInput.type === 'password';
        passwordInput.type = show ? 'text' : 'password';
        passwordIcon.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
    });
}

const form = document.getElementById('loginForm');
if(form) {
    form.addEventListener('submit', function(e) {
        if(!form.checkValidity()) return;
        e.preventDefault();
        const btn = this.querySelector('.btn-login');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + uiManager.t('login_loading');
        btn.style.opacity = '0.8';
        btn.style.pointerEvents = 'none';
        const ball = document.getElementById('ball-anim');
        const video = document.getElementById('ballVideo');
        ball.style.display = 'flex';
        video.currentTime = 0;
        video.play();
        setTimeout(() => { this.submit(); }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/lib/footer.php'; ?>
