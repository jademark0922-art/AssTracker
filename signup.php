<?php
require 'auth.php';
if (!empty($_SESSION['uid'])) redirect('dashboard.php');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssTracker — Create Account</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-brand">
      <a href="index.php" class="logo">AssTracker</a>
      <p class="auth-card-sub">Create your free account</p>
    </div>
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <form method="POST" action="auth.php" class="auth-form">
      <input type="hidden" name="auth_action" value="register">
      <div class="form-group">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" placeholder="Jane Smith"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="password">Password <span class="hint">(min. 8 characters)</span></label>
        <input type="password" id="password" name="password" placeholder="Create a strong password" required minlength="8">
      </div>
      <div class="form-group">
        <label for="confirm">Confirm password</label>
        <input type="password" id="confirm" name="confirm" placeholder="Re-enter your password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
  </div>
</body>
</html>
