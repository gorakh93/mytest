<?php
require_once __DIR__ . '/auth.php';
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

$error = '';
$next = $_GET['next'] ?? ($_POST['next'] ?? '/mytest/user/dashboard.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $error = 'Email and password required.';
    } else {
        if (login_user($email, $password)) {
            header('Location: ' . $next);
            exit;
        } else {
            $error = 'Invalid credentials.';
        }
    }
}

?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login</title>
<link rel="stylesheet" href="/mytest/styles.css"></head><body>
<div class="inner" style="max-width:520px;margin:40px auto">
  <h1>Login</h1>
  <?php if ($error): ?><div class="notice error"><?php echo h($error); ?></div><?php endif; ?>
  <form method="post" action="login.php">
    <input type="hidden" name="next" value="<?php echo h($next); ?>">
    <label>Email<br><input type="email" name="email" required></label><br><br>
    <label>Password<br><input type="password" name="password" required></label><br><br>
    <button type="submit">Login</button>
  </form>
  <p style="margin-top:12px"><a href="/mytest/">Return to site</a></p>
</div>
</body></html>
