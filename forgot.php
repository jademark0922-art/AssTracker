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
  <title>AssTracker — Forgot Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
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
    <p class="auth-desc">Enter the email address associated with your account and Firebase will send you a reset link.</p>
    <form method="POST" action="auth.php" class="auth-form">
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
