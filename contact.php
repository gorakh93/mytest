<?php
require_once __DIR__ . '/header.php';

$errors = [];
$success = false;
$name = '';
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if ($name === '') $errors[] = 'Name is required.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
  if ($message === '') $errors[] = 'Message is required.';

  if (empty($errors)) {
    // In a real site you would send email or store the message.
    $success = true;
  }
}
?>

<main class="container">
  <h1>Contact</h1>

  <?php if ($success): ?>
    <div class="notice success">Thanks <?php echo htmlspecialchars($name); ?> — your message was received.</div>
  <?php else: ?>

    <?php if (!empty($errors)): ?>
      <div class="notice error">
        <ul>
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="contact.php" method="post" class="contact-form">
      <label>Name<br>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
      </label>
      <label>Email<br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
      </label>
      <label>Message<br>
        <textarea name="message"><?php echo htmlspecialchars($message); ?></textarea>
      </label>
      <button type="submit">Send</button>
    </form>

  <?php endif; ?>

</main>

<?php
require_once __DIR__ . '/footer.php';
?>
