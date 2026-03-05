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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .auth-split {
      display: flex;
      width: 100%;
      max-width: 900px;
      background: var(--surface);
      border-radius: var(--r-xl);
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      border: 2px solid var(--border);
    }
    .auth-panel-left {
      background: var(--teal);
      padding: 52px 44px;
      width: 42%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .auth-panel-left::after {
      content: '';
      position: absolute;
      bottom: -60px; right: -60px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
    }
    .auth-panel-left .logo { font-size: 32px; color: #fff; margin-bottom: 24px; }
    .auth-left-heading {
      font-family: var(--font-display);
      font-style: italic;
      font-size: 28px;
      font-weight: 700;
      color: #fff;
      line-height: 1.25;
      margin-bottom: 14px;
    }
    .auth-left-heading span { color: var(--orange); }
    .auth-left-sub {
      font-size: 13px;
      color: rgba(255,255,255,.55);
      line-height: 1.7;
      font-weight: 500;
      margin-bottom: 32px;
    }
    .auth-left-stat {
      background: rgba(255,255,255,.08);
      border-radius: var(--r);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .auth-left-stat-num {
      font-family: var(--font-display);
      font-style: italic;
      font-size: 36px;
      color: var(--orange);
      line-height: 1;
    }
    .auth-left-stat-label { font-size: 12px; color: rgba(255,255,255,.65); font-weight: 700; }
    .auth-panel-right {
      flex: 1;
      padding: 44px 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    @media (max-width: 680px) {
      .auth-split { flex-direction: column; }
      .auth-panel-left { width: 100%; padding: 36px 28px; }
      .auth-panel-right { padding: 36px 28px; }
    }
  </style>
</head>
<body class="auth-body">
  <div class="auth-split">
    <div class="auth-panel-left">
      <a href="index.php" class="logo">AssTracker</a>
      <div class="auth-left-heading">Start your<br><span>productive</span><br>journey</div>
      <div class="auth-left-sub">Join thousands who stay on top of every assignment, deadline, and priority.</div>
      <div class="auth-left-stat">
        <div class="auth-left-stat-num">Free</div>
        <div class="auth-left-stat-label">Free forever<br>No credit card needed</div>
      </div>
    </div>
    <div class="auth-panel-right">
      <div style="margin-bottom:24px;">
        <p style="font-size:22px;font-weight:800;color:var(--text);margin:0;">Create your free account</p>
        <p class="auth-card-sub" style="margin-top:5px;">Fill in your details to get started</p>
      </div>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
      <?php endif; ?>
      <form method="POST" action="auth.php">
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
        <div class="form-row">
          <div class="form-group">
            <label for="password">Password <span class="hint">(min. 8 chars)</span></label>
            <input type="password" id="password" name="password" placeholder="Create password" required minlength="8">
          </div>
          <div class="form-group">
            <label for="confirm">Confirm password</label>
            <input type="password" id="confirm" name="confirm" placeholder="Re-enter" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:6px;">Create account</button>
      </form>
      <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
    </div>
  </div>
</body>
</html>