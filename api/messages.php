<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

// Fix for parents: ensure session is started so $_SESSION['parent_id'] is readable
ensure_session_started();

header('Content-Type: application/json');
header('Cache-Control: no-store');

$id_parent = null;
$sender_type = 'parent';
$id_admin_sender = null;

if (isset($_SESSION['parent_id'])) {
    $id_parent = (int)$_SESSION['parent_id'];
    $sender_type = 'parent';
} elseif (admin_is_logged_in()) {
    // Admin/coach: id_parent must be passed in request
    $sender_type = 'admin';
    $admin = admin_current();
    $id_admin_sender = $admin['id_admin'];
}

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: fetch messages ──────────────────────────────────────────────────────
if ($method === 'GET') {
    // Resolve id_parent
    if ($sender_type === 'admin') {
        $id_parent = (int)($_GET['id_parent'] ?? 0);
    }
    if (!$id_parent) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id_parent']);
        exit;
    }
    $stmt = $pdo->prepare('
        SELECT id_message, id_parent, sender_type, message,
               DATE_FORMAT(date_envoi, "%H:%i") AS heure,
               date_envoi
        FROM messages
        WHERE id_parent = ?
        ORDER BY date_envoi ASC
    ');
    $stmt->execute([$id_parent]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// ── POST: send message ───────────────────────────────────────────────────────
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        // Fallback: try form-encoded
        $input = $_POST;
    }

    $message = trim((string)($input['message'] ?? ''));
    if ($sender_type === 'admin') {
        $id_parent = (int)($input['id_parent'] ?? 0);
    }

    if ($message === '' || !$id_parent) {
        http_response_code(400);
        echo json_encode(['error' => 'Message vide ou destinataire manquant']);
        exit;
    }

    $stmt = $pdo->prepare('
        INSERT INTO messages (id_parent, sender_type, message)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$id_parent, $sender_type, $message]);
    $new_id = (int)$pdo->lastInsertId();

    // Return the new message immediately for real-time insertion
    $msg = $pdo->prepare('
        SELECT id_message, id_parent, sender_type, message,
               DATE_FORMAT(date_envoi, "%H:%i") AS heure
        FROM messages WHERE id_message = ?
    ');
    $msg->execute([$new_id]);
    echo json_encode(['success' => true, 'message' => $msg->fetch(PDO::FETCH_ASSOC)]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
