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
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $conf  = $_POST['confirm']    ?? '';

    if (!$name || !$email || !$pass) { flash('error','All fields are required.'); redirect('signup.php'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error','Please enter a valid email address.'); redirect('signup.php'); }
    if (strlen($pass) < 8) { flash('error','Password must be at least 8 characters.'); redirect('signup.php'); }
    if ($pass !== $conf)   { flash('error','Passwords do not match.'); redirect('signup.php'); }

    $res = fb_register($email, $pass, $name);

    if (isset($res['error'])) {
        $msgs = ['EMAIL_EXISTS'=>'An account with that email already exists.','WEAK_PASSWORD'=>'Password must be at least 8 characters.','INVALID_EMAIL'=>'Please enter a valid email address.'];
        flash('error', $msgs[$res['error']] ?? $res['error']);
        redirect('signup.php');
    }

    $_SESSION['uid']        = $res['uid'];
    $_SESSION['user_name']  = $res['name'];
    $_SESSION['user_email'] = $res['email'];
    $_SESSION['id_token']   = $res['idToken'];

    flash('success', 'Welcome to AssTracker, ' . htmlspecialchars($res['name']) . '!');
    redirect('dashboard.php');
}

// ── Action: login ─────────────────────────────────────────
function doLogin(): void {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';

    if (!$email || !$pass) { flash('error','Email and password are required.'); redirect('login.php'); }

    $res = fb_login($email, $pass);

    if (isset($res['error'])) { flash('error','Incorrect email or password.'); redirect('login.php'); }

    $_SESSION['uid']        = $res['uid'];
    $_SESSION['user_name']  = $res['name'];
    $_SESSION['user_email'] = $res['email'];
    $_SESSION['id_token']   = $res['idToken'];

    redirect('dashboard.php');
}

// ── Action: logout ────────────────────────────────────────
function doLogout(): void { session_destroy(); redirect('login.php'); }

// ── Action: forgot password ───────────────────────────────
function doForgot(): void {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error','Please enter a valid email address.'); redirect('forgot.php'); }
    fb_forgot($email);
    flash('success','If that email is registered, a password reset link has been sent.');
    redirect('forgot.php?sent=1');
}

// ── Route ─────────────────────────────────────────────────
$action = $_POST['auth_action'] ?? '';
if ($action === 'register') doRegister();
if ($action === 'login')    doLogin();
if ($action === 'logout')   doLogout();
if ($action === 'forgot')   doForgot();
