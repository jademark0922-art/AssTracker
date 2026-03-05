<?php
require 'auth.php';
if (!empty($_SESSION['uid'])) redirect('dashboard.php');
$flash = getFlash();
$sent  = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssTracker — Reset Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-brand">
      <a href="index.php" class="logo">AssTracker</a>
      <p class="auth-card-sub">Reset your password</p>
    </div>
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <?php if (!$sent): ?>
    <p class="auth-desc">Enter your email and we'll send you a reset link via Firebase.</p>
    <form method="POST" action="auth.php">
      <input type="hidden" name="auth_action" value="forgot">
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>
    <?php endif; ?>
    <p class="auth-switch">Remembered it? <a href="login.php">Back to log in</a></p>
  </div>
</body>
</html>