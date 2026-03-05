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
  <title>AssTracker — Log In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-brand">
      <a href="index.php" class="logo">AssTracker</a>
      <p class="auth-card-sub">Welcome back</p>
    </div>
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <form method="POST" action="auth.php" class="auth-form">
      <input type="hidden" name="auth_action" value="login">
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">
          Password
          <a href="forgot.php" class="label-link">Forgot password?</a>
        </label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p class="auth-switch">Don't have an account? <a href="signup.php">Sign up free</a></p>
  </div>
</body>
</html>
