<?php
session_start();
require __DIR__ . '/firebase.php';

// ── Auth guard ────────────────────────────────────────────
if (empty($_SESSION['uid'])) {
    header('Location: login.php'); exit;
}

$uid      = $_SESSION['uid'];
$idToken  = $_SESSION['id_token'];
$userName = htmlspecialchars($_SESSION['user_name']);
$flash    = getFlash();

function redirect(string $url): void { header("Location: $url"); exit; }

function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ═══════════════════════════════════════════════════════════
//  JSON API
// ═══════════════════════════════════════════════════════════
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Update display name ───────────────────────────────────
if ($action === 'update_name') {
    $newName = trim($_POST['name'] ?? '');
    if (!$newName) { echo json_encode(['ok'=>false,'msg'=>'Name cannot be empty']); exit; }
    if (strlen($newName) > 50) { echo json_encode(['ok'=>false,'msg'=>'Name too long (max 50 chars)']); exit; }

    // Update Firebase Auth display name
    $authRes = fb_http('POST', FB_AUTH_URL . ':update?key=' . FB_API_KEY, [
        'idToken'      => $idToken,
        'displayName'  => $newName,
        'returnSecureToken' => true,
    ]);

    // Update Firestore user doc
    fb_firestore_update("users/$uid", ['name' => $newName], $idToken);

    if (isset($authRes['error'])) {
        echo json_encode(['ok'=>false,'msg'=>'Failed to update name']);
    } else {
        $_SESSION['user_name'] = $newName;
        // Refresh token if returned
        if (!empty($authRes['idToken'])) {
            $_SESSION['id_token'] = $authRes['idToken'];
        }
        echo json_encode(['ok'=>true,'name'=>$newName]);
    }
    exit;
}

// ── Debug: test Firebase connection & token ───────────────
if ($action === 'debug') {
    $token   = $_SESSION['id_token']      ?? '';
    $refresh = $_SESSION['refresh_token'] ?? '';

    $refreshRes = null;
    $freshToken = $token;
    if ($refresh) {
        $refreshRes = fb_http('POST', FB_REFRESH_URL . '?key=' . FB_API_KEY, [
            'grant_type' => 'refresh_token', 'refresh_token' => $refresh,
        ]);
        if (!empty($refreshRes['id_token'])) $freshToken = $refreshRes['id_token'];
    }

    $writeWithAuth = fb_http('POST', FB_FIRESTORE . '/tasks', fb_fields([
        'userId' => $uid, 'title' => '__debug_auth__',
    ]), $freshToken);

    $writeNoAuth = fb_http('POST', FB_FIRESTORE . '/tasks?key=' . FB_API_KEY, fb_fields([
        'userId' => $uid, 'title' => '__debug_noauth__',
    ]));

    $readTest = fb_http('GET', FB_FIRESTORE . '/tasks?key=' . FB_API_KEY . '&pageSize=1');

    $out = [
        'uid'                    => $uid,
        'has_id_token'           => !empty($token),
        'has_refresh_token'      => !empty($refresh),
        'token_first_20'         => $token ? substr($token, 0, 20).'...' : 'EMPTY',
        'refresh_ok'             => !empty($refreshRes['id_token']),
        'refresh_error'          => $refreshRes['error'] ?? null,
        'write_with_auth_result' => $writeWithAuth,
        'write_no_auth_result'   => $writeNoAuth,
        'read_test_result'       => $readTest,
    ];

    foreach ([$writeWithAuth, $writeNoAuth] as $r) {
        if (!empty($r['name'])) {
            $p = explode('/', $r['name']); $id = end($p);
            fb_http('DELETE', FB_FIRESTORE . "/tasks/$id", [], $freshToken);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'save') {
    $id    = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    if (!$title) { echo json_encode(['ok'=>false,'msg'=>'Title required']); exit; }
    $data = [
        'userId'      => $uid,
        'title'       => $title,
        'description' => trim($_POST['description'] ?? ''),
        'category'    => $_POST['category']    ?? 'Work',
        'priority'    => $_POST['priority']    ?? 'Medium',
        'status'      => $_POST['status']      ?? 'To Do',
        'deadline'    => $_POST['deadline']    ?? '',
        'assigned_to' => trim($_POST['assigned_to'] ?? ''),
    ];
    if ($id) {
        $result = fb_firestore_update("tasks/$id", $data, $idToken);
        if (isset($result['error'])) {
            echo json_encode(['ok'=>false,'msg'=>'Failed to update task: ' . ($result['error']['message'] ?? 'unknown')]);
        } else {
            echo json_encode(['ok'=>true,'id'=>$id]);
        }
    } else {
        $newId = fb_firestore_add('tasks', $data, $idToken);
        if (!$newId) {
            $rawRes = fb_call('POST', FB_FIRESTORE . '/tasks', fb_fields($data));
            echo json_encode(['ok'=>false,'msg'=>'Failed to save task.','firebase_error'=>$rawRes]);
        } else {
            echo json_encode(['ok'=>true,'id'=>$newId]);
        }
    }
    exit;
}

// ── Mark task as complete ─────────────────────────────────
if ($action === 'complete') {
    $id = $_POST['id'] ?? '';
    if ($id) {
        $result = fb_firestore_update("tasks/$id", ['status' => 'Done'], $idToken);
        echo json_encode(isset($result['error']) ? ['ok'=>false] : ['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

// ── Undo complete (move back to In Progress) ──────────────
if ($action === 'undo_complete') {
    $id = $_POST['id'] ?? '';
    if ($id) {
        $result = fb_firestore_update("tasks/$id", ['status' => 'In Progress'], $idToken);
        echo json_encode(isset($result['error']) ? ['ok'=>false] : ['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if ($id) fb_firestore_delete("tasks/$id", $idToken);
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'cycle') {
    $id   = $_POST['id'] ?? '';
    $task = fb_firestore_get("tasks/$id", $idToken);
    if ($task && $task['userId'] === $uid) {
        $order = ['To Do'=>'In Progress','In Progress'=>'Done','Done'=>'To Do'];
        $next  = $order[$task['status']] ?? 'To Do';
        fb_firestore_update("tasks/$id", ['status'=>$next], $idToken);
        echo json_encode(['ok'=>true,'status'=>$next]);
    } else {
        echo json_encode(['ok'=>false]);
    }
    exit;
}

if ($action === 'get') {
    $id   = $_GET['id'] ?? '';
    $task = fb_firestore_get("tasks/$id", $idToken);
    echo json_encode(($task && $task['userId'] === $uid) ? $task : []);
    exit;
}

if ($action === 'list') {
    $tasks = fb_firestore_query('tasks', [['userId','EQUAL',$uid]], $idToken);
    $cat = $_GET['category'] ?? 'All';
    $sta = $_GET['status']   ?? 'All';
    $pri = $_GET['priority'] ?? 'All';
    $q   = strtolower($_GET['q'] ?? '');
    if ($cat !== 'All') $tasks = array_filter($tasks, fn($t) => ($t['category']??'') === $cat);
    if ($sta !== 'All') $tasks = array_filter($tasks, fn($t) => ($t['status']  ??'') === $sta);
    if ($pri !== 'All') $tasks = array_filter($tasks, fn($t) => ($t['priority']??'') === $pri);
    if ($q)             $tasks = array_filter($tasks, fn($t) =>
        str_contains(strtolower($t['title']??''), $q) ||
        str_contains(strtolower($t['assigned_to']??''), $q)
    );
    $tasks = array_values($tasks);
    $sort  = $_GET['sort'] ?? 'deadline';
    $pOrd  = ['Urgent'=>1,'High'=>2,'Medium'=>3,'Low'=>4];
    usort($tasks, function($a, $b) use ($sort, $pOrd) {
        if ($sort === 'priority') return ($pOrd[$a['priority']]??9) <=> ($pOrd[$b['priority']]??9);
        if ($sort === 'title')    return strcasecmp($a['title']??'', $b['title']??'');
        $da = $a['deadline'] ?? ''; $db = $b['deadline'] ?? '';
        if (!$da && !$db) return 0;
        if (!$da) return 1;
        if (!$db) return -1;
        return strcmp($da, $db);
    });
    echo json_encode($tasks);
    exit;
}

if ($action === 'stats') {
    $tasks = fb_firestore_query('tasks', [['userId','EQUAL',$uid]], $idToken);
    $now   = date('Y-m-d');
    echo json_encode([
        'total'      => count($tasks),
        'done'       => count(array_filter($tasks, fn($t) => ($t['status']??'') === 'Done')),
        'inProgress' => count(array_filter($tasks, fn($t) => ($t['status']??'') === 'In Progress')),
        'overdue'    => count(array_filter($tasks, fn($t) =>
            !empty($t['deadline']) && $t['deadline'] < $now && ($t['status']??'') !== 'Done'
        )),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AssTracker — Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
    <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="favicon3.svg">
  <link rel="icon" type="image/png" sizes="256x256" href="favicon3-256.png">
  <link rel="shortcut icon" href="favicon3.ico">
  <style>
    .hero-banner {
      background: var(--teal);
      border-radius: var(--r-xl);
      padding: 32px 40px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 12px 40px rgba(30,61,58,.3);
    }
    .hero-banner::before {
      content: '';
      position: absolute;
      right: -40px; top: -40px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
      pointer-events: none;
    }
    .hero-banner::after {
      content: '';
      position: absolute;
      right: 80px; bottom: -60px;
      width: 140px; height: 140px;
      border-radius: 50%;
      background: rgba(255,255,255,.04);
      pointer-events: none;
    }
    .hero-eyebrow {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .12em;
      color: rgba(255,255,255,.5);
      margin-bottom: 8px;
    }
    .hero-title {
      font-family: 'Montserrat', sans-serif;
      font-style: italic;
      font-size: clamp(22px, 3vw, 32px);
      font-weight: 700;
      color: #fff;
      line-height: 1.2;
      margin-bottom: 6px;
    }
    .hero-title span { color: var(--orange); font-style: normal; }
    .hero-sub-text {
      font-size: 13px;
      color: rgba(255,255,255,.5);
      font-weight: 600;
    }
    .hero-today {
      font-size: 12px;
      color: rgba(255,255,255,.6);
      font-weight: 700;
      background: rgba(255,255,255,.08);
      padding: 8px 16px;
      border-radius: 50px;
      flex-shrink: 0;
    }

    /* ── Editable name in header ── */
    .header-user-wrap {
      display: flex;
      align-items: center;
      gap: 6px;
      position: relative;
    }
    .header-user {
      background: var(--teal);
      color: rgba(255,255,255,.9);
      padding: 7px 16px;
      border-radius: 50px;
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 7px;
      transition: background .2s;
      border: 2px solid transparent;
      user-select: none;
    }
    .header-user:hover {
      background: var(--teal-light);
    }
    .header-user .edit-icon {
      width: 13px; height: 13px;
      opacity: .55;
      flex-shrink: 0;
    }

    /* Name edit popover */
    .name-popover {
      position: absolute;
      top: calc(100% + 10px);
      left: 0;
      background: var(--surface);
      border: 2px solid var(--border);
      border-radius: var(--r-lg);
      padding: 18px 18px 14px;
      box-shadow: var(--shadow-lg);
      z-index: 200;
      width: 270px;
      display: none;
      animation: popIn .22s cubic-bezier(.34,1.56,.64,1);
    }
    .name-popover.open { display: block; }
    @keyframes popIn {
      from { transform: scale(.92) translateY(-6px); opacity: 0; }
      to   { transform: scale(1)   translateY(0);    opacity: 1; }
    }
    .name-popover-label {
      font-size: 10.5px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .1em;
      color: var(--muted);
      margin-bottom: 8px;
    }
    .name-popover-input {
      width: 100%;
      border: 2px solid var(--border);
      border-radius: var(--r-sm);
      padding: 9px 12px;
      font-family: var(--font-body);
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
      background: var(--surface2);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      margin-bottom: 10px;
    }
    .name-popover-input:focus {
      border-color: var(--orange);
      box-shadow: 0 0 0 3px rgba(245,166,35,.12);
      background: var(--surface);
    }
    .name-popover-actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
    }
    .name-popover-actions .btn-sm {
      padding: 5px 14px;
      font-size: 12px;
    }
    .name-save-btn {
      background: var(--teal);
      color: #fff;
      border-color: transparent;
    }
    .name-save-btn:hover {
      background: var(--teal-light);
      color: #fff;
    }
    .name-cancel-btn {
      background: var(--surface2);
      color: var(--text2);
      border-color: var(--border);
    }

    /* ── Responsive overrides (dashboard-specific) ── */

    /* ≤860px: header wraps, brand shrinks */
    @media (max-width: 860px) {
      .header {
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px 16px;
      }
      .brand { flex: 1; }
      .header-actions {
        flex-wrap: wrap;
        gap: 7px;
        width: 100%;
        justify-content: flex-end;
      }
    }

    /* ≤600px: full mobile layout */
    @media (max-width: 600px) {
      .header {
        padding: 10px 14px;
        gap: 8px;
      }
      .brand { flex: 1 1 100%; }
      .header-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 7px;
        align-items: stretch;
      }
      .header-user-wrap { grid-column: 1 / -1; }
      .header-user { width: 100%; justify-content: space-between; }
      .header-actions .btn {
        width: 100%;
        justify-content: center;
        padding: 9px 10px;
        font-size: 12.5px;
      }
      /* Hide list view toggle — board is already 1 col */
      .header-actions #toggleView { display: none; }
      /* New Task spans both cols (only button shown) */
      .header-actions #btnNewTask { grid-column: 1 / -1; }
      /* Logout form spans full width */
      .header-actions form { grid-column: 1 / -1; margin: 0; }
      .header-actions form .btn { width: 100%; justify-content: center; }
      /* Name popover full width */
      .name-popover { width: calc(100vw - 28px); left: 0; right: 0; }
      /* Hero banner stacks */
      .hero-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px 18px;
        gap: 10px;
      }
      .hero-today { align-self: flex-start; }
      /* Filters stack */
      .filters { flex-direction: column; gap: 8px; padding: 12px 14px; }
      .filters input, .filters select { min-width: 100%; width: 100%; }
      .task-count { margin-left: 0; text-align: center; }
      /* Tabs */
      .tabs-bar { flex-direction: column; align-items: stretch; gap: 8px; }
      .tabs-left { overflow-x: auto; -webkit-overflow-scrolling: touch; display: flex; }
    }

    /* ≤400px: single column everything */
    @media (max-width: 400px) {
      .header-actions { grid-template-columns: 1fr; }
      .header-actions .btn,
      .header-actions #btnNewTask,
      .header-user-wrap,
      .header-actions form { grid-column: 1 / -1; }
    }
  </style>
</head>
<body>

  <header class="header">
    <div class="brand">
      <div class="logo">AssTracker</div>
      <div class="logo-sub">Universal Assignment Tracker</div>
    </div>
    <div class="header-actions">

      <!-- Editable name chip -->
      <div class="header-user-wrap" id="nameWrap">
        <div class="header-user" id="nameChip" onclick="toggleNamePopover()" title="Click to edit your name">
          <span id="headerNameText">Hi, <?= $userName ?></span>
          <svg class="edit-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 013.182 3.182L7.5 19.213l-4.5 1.125 1.125-4.5L16.862 3.487z"/>
          </svg>
        </div>
        <div class="name-popover" id="namePopover">
          <div class="name-popover-label">Edit your name</div>
          <input
            type="text"
            class="name-popover-input"
            id="nameInput"
            maxlength="50"
            placeholder="Your display name"
            value="<?= $userName ?>"
            onkeydown="handleNameKey(event)"
          >
          <div class="name-popover-actions">
            <button type="button" class="btn-sm name-cancel-btn" onclick="closeNamePopover()">Cancel</button>
            <button type="button" class="btn-sm name-save-btn" onclick="saveName()">Save</button>
          </div>
        </div>
      </div>

      <button type="button" class="btn btn-ghost" id="toggleView">List View</button>
      <button type="button" class="btn btn-primary" id="btnNewTask">+ New Task</button>
      <form method="POST" action="auth.php" style="margin:0">
        <input type="hidden" name="auth_action" value="logout">
        <button type="submit" class="btn btn-ghost btn-danger">Log out</button>
      </form>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="page-alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <main class="main">

    <!-- Hero greeting -->
    <div class="hero-banner">
      <div class="hero-banner-text">
        <div class="hero-eyebrow">Dashboard</div>
        <div class="hero-title">Let's become more <span>Productive</span></div>
        <div class="hero-sub-text">Stay on top of every task, deadline &amp; priority.</div>
      </div>
      <div class="hero-today" id="heroDayLabel">Loading…</div>
    </div>

    <!-- Stats -->
    <div class="stats" id="statsGrid"></div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <div class="tabs-left">
        <button class="tab-btn tab-active" data-tab="active">
          Active Tasks
        </button>
        <button class="tab-btn" data-tab="completed">
          Completed
          <span class="tab-badge" id="completedBadge">0</span>
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <input type="text" id="searchInput" placeholder="Search tasks...">
      <select id="fCategory">
        <option>All</option>
        <option>Work</option><option>Study</option><option>Personal</option>
        <option>Freelance</option><option>Health</option><option>Finance</option><option>Other</option>
      </select>
      <select id="fStatus">
        <option>All</option><option>To Do</option><option>In Progress</option><option>Done</option>
      </select>
      <select id="fPriority">
        <option>All</option><option>Low</option><option>Medium</option><option>High</option><option>Urgent</option>
      </select>
      <select id="fSort">
        <option value="deadline">Deadline</option>
        <option value="priority">Priority</option>
        <option value="title">Title A–Z</option>
      </select>
      <span class="task-count" id="taskCount"></span>
    </div>

    <div id="boardView" class="board"></div>
    <div id="listView"  class="list-view hidden"></div>
    <div id="completedView" class="hidden"></div>
  </main>

  <!-- Task Modal -->
  <div class="overlay hidden" id="overlay">
    <div class="modal">
      <h2 class="modal-title" id="modalTitle">New Task</h2>
      <input type="hidden" id="taskId">
      <div class="form-group">
        <label for="fTitle">Title <span class="req">*</span></label>
        <input type="text" id="fTitle" placeholder="What needs to be done?">
      </div>
      <div class="form-group">
        <label for="fDesc">Description</label>
        <textarea id="fDesc" rows="3" placeholder="Optional details…"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="fCat">Category</label>
          <select id="fCat">
            <option>Work</option><option>Study</option><option>Personal</option>
            <option>Freelance</option><option>Health</option><option>Finance</option><option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label for="fPri">Priority</label>
          <select id="fPri"><option>Low</option><option selected>Medium</option><option>High</option><option>Urgent</option></select>
        </div>
        <div class="form-group">
          <label for="fStat">Status</label>
          <select id="fStat"><option>To Do</option><option>In Progress</option><option>Done</option></select>
        </div>
        <div class="form-group">
          <label for="fDeadline">Deadline</label>
          <input type="date" id="fDeadline">
        </div>
      </div>
      <div class="form-group">
        <label for="fAssigned">Assigned To</label>
        <input type="text" id="fAssigned" placeholder="Name or team member">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-ghost" id="btnCancel">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnSave">Save Task</button>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="overlay hidden" id="deleteModal">
    <div class="modal" style="max-width:400px;text-align:center;">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--red-pale);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:26px;">🗑</div>
      <h2 class="modal-title" style="justify-content:center;border:none;padding:0;margin-bottom:10px;">Delete Task?</h2>
      <p style="color:var(--muted);font-size:14px;margin-bottom:28px;">This action cannot be undone. The task will be permanently removed.</p>
      <div class="modal-actions" style="justify-content:center;gap:12px;">
        <button type="button" class="btn btn-ghost" id="btnCancelDelete" style="min-width:100px;">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnConfirmDelete" style="min-width:100px;background:var(--red);border-color:var(--red);">Delete</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>
  <script src="app.js"></script>
  <script>
    // Hero day label
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const now = new Date();
    const nth = n => { const s=['th','st','nd','rd']; const v=n%100; return n+(s[(v-20)%10]||s[v]||s[0]); };
    document.getElementById('heroDayLabel').textContent =
      days[now.getDay()] + ', ' + months[now.getMonth()] + ' ' + nth(now.getDate());

    // ── Name edit ─────────────────────────────────────────
    function toggleNamePopover() {
      const pop = document.getElementById('namePopover');
      const input = document.getElementById('nameInput');
      const isOpen = pop.classList.contains('open');
      if (isOpen) {
        closeNamePopover();
      } else {
        pop.classList.add('open');
        // Small delay so the element is visible before focusing
        setTimeout(() => { input.focus(); input.select(); }, 50);
      }
    }

    function closeNamePopover() {
      document.getElementById('namePopover').classList.remove('open');
    }

    function handleNameKey(e) {
      if (e.key === 'Enter')  saveName();
      if (e.key === 'Escape') closeNamePopover();
    }

    async function saveName() {
      const input   = document.getElementById('nameInput');
      const newName = input.value.trim();
      if (!newName) { showToast('Name cannot be empty.'); return; }

      const fd = new FormData();
      fd.append('action', 'update_name');
      fd.append('name',   newName);

      try {
        const res  = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
          document.getElementById('headerNameText').textContent = 'Hi, ' + data.name;
          closeNamePopover();
          showToast('Name updated! ✓');
        } else {
          showToast('Error: ' + (data.msg || 'Could not update name.'));
        }
      } catch (err) {
        showToast('Network error. Please try again.');
      }
    }

    // Close popover when clicking outside
    document.addEventListener('click', function(e) {
      const wrap = document.getElementById('nameWrap');
      if (wrap && !wrap.contains(e.target)) {
        closeNamePopover();
      }
    });
  </script>
</body>
</html>