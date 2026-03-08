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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
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
      font-family: 'Montserrat', sans-serif;
      font-style: normal;
      font-size: 32px;
      font-weight: 900;
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
      <div class="auth-left-heading">Welcome</div>
      <div class="auth-left-sub">Your tasks are waiting. Log in with your username to pick up right where you left off.</div>
      <div class="auth-left-pills">
        <div class="auth-left-pill"><span class="auth-left-pill-dot"></span> Board &amp; List view</div>
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
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="your_username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus
                 autocomplete="username">
        </div>
        <div class="form-group">
          <label for="password">
            Password
            <a href="forgot.php" class="label-link">Forgot password?</a>
          </label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-label="Show password">
              <!-- Eye icon (shown when password is hidden) -->
              <svg id="password-eye-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <!-- Eye-off icon (shown when password is visible) -->
              <svg id="password-eye-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.07-3.346M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.423 5.337M3 3l18 18"/>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">Log in</button>
      </form>
      <p class="auth-switch">Don't have an account? <a href="signup.php">Sign up</a></p>
    </div>
  </div>

  <script>
    function togglePw(inputId, btn) {
      const input   = document.getElementById(inputId);
      const showIcon = document.getElementById(inputId + '-eye-show');
      const hideIcon = document.getElementById(inputId + '-eye-hide');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      showIcon.style.display = isHidden ? 'none'  : '';
      hideIcon.style.display = isHidden ? ''      : 'none';
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    }
  </script>
</body>
</html>