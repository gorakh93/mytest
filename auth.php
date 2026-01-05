<?php
// Simple auth helper: starts session, provides login/logout and helpers
require_once __DIR__ . '/db/connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function current_user() {
    global $pdo;
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare('SELECT id,name,email,role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        $next = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /mytest/login.php?next=' . urlencode($next));
        exit;
    }
}

function require_admin() {
    require_login();
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        http_response_code(403);
        echo 'Forbidden - admin only.';
        exit;
    }
}

function login_user($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $u['id'];
        return true;
    }
    return false;
}

function logout_user() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
