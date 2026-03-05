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
      width: 45%;
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
    .auth-panel-left::before {
      content: '';
      position: absolute;
      top: -40px; left: -40px;
      width: 120px; height: 120px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
    }
    .auth-panel-left .logo { font-size: 32px; color: #fff; margin-bottom: 28px; }
    .auth-left-heading {
      font-family: var(--font-display);
      font-style: italic;
      font-size: 32px;
      font-weight: 700;
      color: #fff;
      line-height: 1.2;
      margin-bottom: 14px;
    }
    .auth-left-heading span { color: var(--orange); font-style: normal; }
    .auth-left-sub {
      font-size: 13.5px;
      color: rgba(255,255,255,.55);
      line-height: 1.7;
      font-weight: 500;
      margin-bottom: 36px;
    }
    .auth-left-pills { display: flex; flex-direction: column; gap: 10px; }
    .auth-left-pill {
      display: flex; align-items: center; gap: 10px;
      font-size: 12.5px; font-weight: 700;
      color: rgba(255,255,255,.8);
    }
    .auth-left-pill-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--orange); flex-shrink: 0;
    }
    .auth-panel-right {
      flex: 1;
      padding: 52px 44px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .auth-panel-right .auth-brand { text-align: left; }
    .auth-panel-right .auth-brand .logo { font-size: 26px; }
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
      <div class="auth-left-heading">Welcome<br>back</div>
      <div class="auth-left-sub">Your tasks are waiting. Log in to pick up right where you left off.</div>
      <div class="auth-left-pills">
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Board & List view</div>
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Deadline tracking</div>
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Firebase cloud sync</div>
      </div>
    </div>
    <div class="auth-panel-right">
      <div class="auth-brand" style="margin-bottom:28px;">
        <p class="auth-card-sub" style="font-size:22px;font-weight:800;color:var(--text);margin:0;">Log in to your account</p>
        <p class="auth-card-sub" style="margin-top:4px;">Enter your credentials below</p>
      </div>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
      <?php endif; ?>
      <form method="POST" action="auth.php">
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
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">Log in</button>
      </form>
      <p class="auth-switch">Don't have an account? <a href="signup.php">Sign up free</a></p>
    </div>
  </div>
</body>
</html>