<?php
require __DIR__ . '/../includes/config.php';
$pageTitle = 'Staff login';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Safe query, but the stored hash is unsalted MD5 (INTENTIONALLY WEAK).
    $stmt = $db->prepare("SELECT username, password_hash FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && hash_equals($user['password_hash'], md5($password))) {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $user['username'];
        header('Location: /admin/');
        exit;
    }
    $error = 'That login does not match our staff list.';
}

require __DIR__ . '/../includes/header.php';
?>
<section class="section narrow">
  <h1>Staff login</h1>
  <p class="muted">Back office for Neil, Avi and the crew.</p>
  <?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
  <form class="form" method="post" action="/admin/login.php">
    <label>Username <input type="text" name="username" autocomplete="username" required></label>
    <label>Password <input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn" type="submit">Sign in</button>
  </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
