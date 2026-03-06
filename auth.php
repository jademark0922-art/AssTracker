<?php
session_start();
require __DIR__ . '/firebase.php';

// ── Helper: redirect ──────────────────────────────────────
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ── Helper: flash message ─────────────────────────────────
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ── Action: register ──────────────────────────────────────
function doRegister(): void {
    $name     = trim($_POST['name']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password']      ?? '';
    $conf     = $_POST['confirm']       ?? '';

    if (!$name || !$username || !$pass) { flash('error','All fields are required.'); redirect('signup.php'); }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) { flash('error','Username must be 3–20 characters and contain only letters, numbers, or underscores.'); redirect('signup.php'); }
    if (strlen($pass) < 8) { flash('error','Password must be at least 8 characters.'); redirect('signup.php'); }
    if ($pass !== $conf)   { flash('error','Passwords do not match.'); redirect('signup.php'); }

    // Check if username already taken
    $existingEmail = fb_get_email_by_username($username);
    if ($existingEmail) { flash('error','That username is already taken. Please choose another.'); redirect('signup.php'); }

    // Auto-generate a Firebase-compatible email from username
    $email = strtolower($username) . '@asstracker.app';

    $res = fb_register($email, $pass, $name, $username);

    if (isset($res['error'])) {
        $msgs = ['EMAIL_EXISTS'=>'That username is already registered.','WEAK_PASSWORD'=>'Password must be at least 8 characters.'];
        flash('error', $msgs[$res['error']] ?? $res['error']);
        redirect('signup.php');
    }

    $_SESSION['uid']           = $res['uid'];
    $_SESSION['user_name']     = $res['name'];
    $_SESSION['username']      = $res['username'];
    $_SESSION['user_email']    = $res['email'];
    $_SESSION['id_token']      = $res['idToken'];
    $_SESSION['refresh_token'] = $res['refreshToken'];

    flash('success', 'Welcome to AssTracker, ' . htmlspecialchars($res['name']) . '!');
    redirect('dashboard.php');
}

// ── Action: login ─────────────────────────────────────────
function doLogin(): void {
    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password']      ?? '';

    if (!$username || !$pass) { flash('error','Username and password are required.'); redirect('login.php'); }

    $res = fb_login($username, $pass);

    if (isset($res['error'])) { flash('error','Incorrect username or password.'); redirect('login.php'); }

    $_SESSION['uid']           = $res['uid'];
    $_SESSION['user_name']     = $res['name'];
    $_SESSION['username']      = $res['username'];
    $_SESSION['user_email']    = $res['email'];
    $_SESSION['id_token']      = $res['idToken'];
    $_SESSION['refresh_token'] = $res['refreshToken'];

    redirect('dashboard.php');
}

// ── Action: logout ────────────────────────────────────────
function doLogout(): void { session_destroy(); redirect('login.php'); }

// ── Action: forgot — step 1 verify captcha ───────────────
function doForgotVerify(): void {
    $username = trim($_POST['username'] ?? '');
    $input    = trim($_POST['captcha_input'] ?? '');
    $expected = $_SESSION['captcha_num'] ?? '';

    if (!$username) { flash('error', 'Please enter your username.'); redirect('forgot.php'); }
    if (!$input)    { flash('error', 'Please enter the number shown.'); redirect('forgot.php'); }

    // Refresh captcha for next attempt regardless
    $_SESSION['captcha_num'] = strval(rand(10000, 99999));

    if ($input !== $expected) {
        flash('error', 'The number you entered is incorrect. Please try again.');
        redirect('forgot.php');
    }

    // Check username exists
    $email = fb_get_email_by_username($username);
    if (!$email) {
        flash('error', 'No account found with that username.');
        redirect('forgot.php');
    }

    // Store verified username in session for step 2
    $_SESSION['reset_username'] = $username;
    redirect('forgot.php?step=2&u=' . urlencode($username));
}

// ── Action: forgot — step 2 reset password ────────────────
function doForgotReset(): void {
    $username = $_SESSION['reset_username'] ?? '';
    if (!$username) { flash('error', 'Session expired. Please start over.'); redirect('forgot.php'); }

    $newPass = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) { flash('error', 'Password must be at least 8 characters.'); redirect('forgot.php?step=2&u=' . urlencode($username)); }
    if ($newPass !== $confirm) { flash('error', 'Passwords do not match.'); redirect('forgot.php?step=2&u=' . urlencode($username)); }

    $email = fb_get_email_by_username($username);
    if (!$email) { flash('error', 'Account not found.'); redirect('forgot.php'); }


    $res = fb_http('POST', FB_AUTH_URL . ':sendOobCode?key=' . FB_API_KEY, [
        'requestType' => 'PASSWORD_RESET',
        'email'       => $email,
    ]);

    unset($_SESSION['reset_username']);

    if (isset($res['error'])) {
        flash('error', 'Could not send reset link. Please try again.');
        redirect('forgot.php');
    }

    flash('success', 'Your Password is Successfuly reset!');
    redirect('login.php');
}

// ── Route ─────────────────────────────────────────────────
$action = $_POST['auth_action'] ?? '';
if ($action === 'register')      doRegister();
if ($action === 'login')         doLogin();
if ($action === 'logout')        doLogout();
if ($action === 'forgot_verify') doForgotVerify();
if ($action === 'forgot_reset')  doForgotReset();