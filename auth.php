<?php
// Simple auth helper: starts session, provides login/logout and helpers
require_once __DIR__ . '/db/connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function current_user() {
    global $mysqli;
    if (empty($_SESSION['user_id'])) return null;
    $id = (int)$_SESSION['user_id'];
    $stmt = $mysqli->prepare('SELECT id,name,email,role FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
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
    global $mysqli;
    $stmt = $mysqli->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $u = $res ? $res->fetch_assoc() : null;
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
