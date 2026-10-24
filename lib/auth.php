<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/app.php';

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function admin_is_logged_in(): bool
{
    ensure_session_started();
    return isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_is_logged_in()) {
        header('Location: ' . url_path('/admin/login.php'));
        exit;
    }
}

function admin_current(): ?array
{
    ensure_session_started();
    if (!admin_is_logged_in()) {
        return null;
    }
    return [
        'id_admin' => $_SESSION['admin_id'],
        'nom' => $_SESSION['admin_nom'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'role' => $_SESSION['admin_role'] ?? 'subadmin',
    ];
}

function admin_login_by_credentials(string $email, string $password): bool
{
    $email = trim(mb_strtolower($email));
    if ($email === '' || $password === '') {
        return false;
    }

    $stmt = db()->prepare('SELECT id_admin, nom, email, mot_de_passe, role FROM admin WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if (!$admin) {
        return false;
    }

    $hash = (string)($admin['mot_de_passe'] ?? '');
    if ($hash === '') {
        return false;
    }

    $ok = password_verify($password, $hash);
    if (!$ok) {
        return false;
    }

    ensure_session_started();
    $_SESSION['admin_id'] = (int)$admin['id_admin'];
    $_SESSION['admin_nom'] = $admin['nom'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'] ?? 'subadmin';

    return true;
}

function admin_logout(): void
{
    ensure_session_started();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function csrf_token(): string
{
    ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf(): void
{
    ensure_session_started();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== '') {
            json_response(['ok' => false, 'error' => 'Invalid CSRF token.'], 403);
        } else {
            exit('CSRF token mismatch.');
        }
    }
}

// ── Rate Limiting ────────────────────────────────────────────────
function rate_limit_key(string $context, string $identifier): string
{
    return $context . '_' . md5($identifier);
}

function rate_limit_check(string $context, string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): ?int
{
    $key = rate_limit_key($context, $identifier);
    $file = sys_get_temp_dir() . '/rl_' . $key . '.json';
    
    $data = ['attempts' => 0, 'first_at' => 0];
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        $data = json_decode($raw, true) ?: $data;
    }
    
    $now = time();
    // Reset window if expired
    if ($now - ($data['first_at'] ?? 0) > $windowSeconds) {
        $data = ['attempts' => 0, 'first_at' => $now];
    }
    
    if (($data['attempts'] ?? 0) >= $maxAttempts) {
        $remaining = $windowSeconds - ($now - ($data['first_at'] ?? 0));
        return max(0, $remaining);
    }
    
    return null; // not blocked
}

function rate_limit_record(string $context, string $identifier, int $windowSeconds = 300): void
{
    $key = rate_limit_key($context, $identifier);
    $file = sys_get_temp_dir() . '/rl_' . $key . '.json';
    
    $data = ['attempts' => 0, 'first_at' => 0];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
    }
    
    $now = time();
    if ($now - ($data['first_at'] ?? 0) > $windowSeconds) {
        $data = ['attempts' => 1, 'first_at' => $now];
    } else {
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
    }
    
    file_put_contents($file, json_encode($data), LOCK_EX);
}

function rate_limit_reset(string $context, string $identifier): void
{
    $key = rate_limit_key($context, $identifier);
    $file = sys_get_temp_dir() . '/rl_' . $key . '.json';
    if (file_exists($file)) {
        unlink($file);
    }
}

