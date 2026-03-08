<?php
require 'auth.php';
if (!empty($_SESSION['uid'])) redirect('dashboard.php');

if (empty($_SESSION['captcha_num']) || isset($_GET['refresh'])) {
    $_SESSION['captcha_num'] = strval(rand(10000, 99999));
}

$flash = getFlash();
$step  = $_GET['step'] ?? '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>AssTracker — Reset Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .captcha-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 6px;
      flex-wrap: wrap;
    }
    #captchaCanvas {
      border-radius: 10px;
      border: 2px solid var(--border);
      background: var(--surface-2, #f1f5f4);
      display: block;
      cursor: pointer;
    }
    .captcha-hint {
      font-size: 11px;
      color: var(--muted);
      margin-bottom: 14px;
    }
    .captcha-refresh {
      font-size: 11px;
      color: var(--teal-mid, #2d7a6e);
      font-weight: 700;
      cursor: pointer;
      text-decoration: underline;
      background: none;
      border: none;
      padding: 0;
      white-space: nowrap;
    }

    @media (max-width: 520px) {
      body.auth-body {
        align-items: flex-start !important;
        padding: 0 !important;
        min-height: 100dvh;
      }
      .auth-card {
        width: 100% !important;
        max-width: 100% !important;
        min-height: 100dvh !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        border: none !important;
        padding: 32px 22px 48px !important;
      }
      input[type="text"],
      input[type="password"] {
        font-size: 16px !important;
        padding: 12px 14px !important;
      }
      .btn-block {
        padding: 14px !important;
        font-size: 16px !important;
      }
      #captchaCanvas {
        width: 160px !important;
        height: 52px !important;
      }
    }
  </style>
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-brand">
      <a href="index.php" class="logo">AssignmentTracker</a>
      <p class="auth-card-sub">Reset your password</p>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if ($step === '1'): ?>
      <p class="auth-desc">Enter your username and type the number you see below to verify it's you.</p>
      <form method="POST" action="auth.php">
        <input type="hidden" name="auth_action" value="forgot_verify">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="your_username"
                 required autofocus autocomplete="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Type the number shown below</label>
          <div class="captcha-wrap">
            <canvas id="captchaCanvas" width="180" height="56"></canvas>
            <button type="button" class="captcha-refresh" onclick="refreshCaptcha()">↻ New number</button>
          </div>
          <p class="captcha-hint">Click the refresh button to get a new number.</p>
          <input type="text" name="captcha_input" id="captchaInput"
                 placeholder="Enter the number" required autocomplete="off"
                 maxlength="5" inputmode="numeric">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Verify &amp; Continue</button>
      </form>

    <?php elseif ($step === '2'): ?>
      <p class="auth-desc">Verified! Now set your new password.</p>
      <form method="POST" action="auth.php">
        <input type="hidden" name="auth_action" value="forgot_reset">
        <input type="hidden" name="username" value="<?= htmlspecialchars($_GET['u'] ?? '') ?>">
        <div class="form-group">
          <label for="new_password">New Password <span class="hint">(min. 8 chars)</span></label>
          <div class="pw-wrap">
            <input type="password" id="new_password" name="new_password"
                   placeholder="Create new password" required minlength="8" autofocus autocomplete="new-password">
            <button type="button" class="pw-toggle" onclick="togglePw('new_password', this)" aria-label="Show password">
              <svg id="new_password-eye-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg id="new_password-eye-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.07-3.346M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.423 5.337M3 3l18 18"/>
              </svg>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="pw-wrap">
            <input type="password" id="confirm_password" name="confirm_password"
                   placeholder="Confirm new password" required autocomplete="new-password">
            <button type="button" class="pw-toggle" onclick="togglePw('confirm_password', this)" aria-label="Show password">
              <svg id="confirm_password-eye-show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg id="confirm_password-eye-hide" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.07-3.346M6.228 6.228A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.423 5.337M3 3l18 18"/>
              </svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
      </form>
    <?php endif; ?>

    <p class="auth-switch">Remembered it? <a href="login.php">Back to log in</a></p>
  </div>

  <script>
    function togglePw(inputId, btn) {
      const input    = document.getElementById(inputId);
      const showIcon = document.getElementById(inputId + '-eye-show');
      const hideIcon = document.getElementById(inputId + '-eye-hide');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      showIcon.style.display = isHidden ? 'none' : '';
      hideIcon.style.display = isHidden ? ''     : 'none';
      btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    }

    const captchaNum = <?= json_encode($_SESSION['captcha_num']) ?>;

    function drawCaptcha(num) {
      const canvas = document.getElementById('captchaCanvas');
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      const W = canvas.width, H = canvas.height;
      ctx.fillStyle = '#eef4f2';
      ctx.fillRect(0, 0, W, H);
      for (let i = 0; i < 6; i++) {
        ctx.strokeStyle = `rgba(${rand(80,160)},${rand(80,160)},${rand(80,160)},0.35)`;
        ctx.lineWidth = rand(1, 2);
        ctx.beginPath();
        ctx.moveTo(rand(0, W), rand(0, H));
        ctx.lineTo(rand(0, W), rand(0, H));
        ctx.stroke();
      }
      for (let i = 0; i < 40; i++) {
        ctx.fillStyle = `rgba(${rand(60,180)},${rand(60,180)},${rand(60,180)},0.3)`;
        ctx.beginPath();
        ctx.arc(rand(0, W), rand(0, H), rand(1, 2.5), 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.textBaseline = 'middle';
      const digits = String(num).split('');
      const startX = 18, spacing = 30;
      digits.forEach((d, i) => {
        ctx.save();
        const x = startX + i * spacing + rand(-2, 2);
        const y = H / 2 + rand(-4, 4);
        ctx.translate(x, y);
        ctx.rotate((rand(-15, 15)) * Math.PI / 180);
        ctx.font = `bold ${rand(22, 27)}px monospace`;
        ctx.shadowColor = 'rgba(0,0,0,0.18)';
        ctx.shadowBlur = rand(2, 5);
        ctx.fillStyle = `rgb(${rand(20,80)},${rand(80,130)},${rand(80,120)})`;
        ctx.fillText(d, 0, 0);
        ctx.restore();
      });
    }

    function rand(min, max) {
      return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function refreshCaptcha() {
      window.location.href = '?refresh=1';
    }

    if (document.getElementById('captchaCanvas')) {
      drawCaptcha(captchaNum);
    }
  </script>
</body>
</html>