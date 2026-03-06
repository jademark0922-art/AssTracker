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

// ── Debug: test Firebase connection & token ───────────────
if ($action === 'debug') {
    $token   = $_SESSION['id_token']      ?? '';
    $refresh = $_SESSION['refresh_token'] ?? '';

    // 1. Try token refresh
    $refreshRes = null;
    $freshToken = $token;
    if ($refresh) {
        $refreshRes = fb_http('POST', FB_REFRESH_URL . '?key=' . FB_API_KEY, [
            'grant_type' => 'refresh_token', 'refresh_token' => $refresh,
        ]);
        if (!empty($refreshRes['id_token'])) $freshToken = $refreshRes['id_token'];
    }

    // 2. Try Firestore write WITH auth token
    $writeWithAuth = fb_http('POST', FB_FIRESTORE . '/tasks', fb_fields([
        'userId' => $uid, 'title' => '__debug_auth__',
    ]), $freshToken);

    // 3. Try Firestore write WITHOUT auth (using API key only) — tests open rules
    $writeNoAuth = fb_http('POST', FB_FIRESTORE . '/tasks?key=' . FB_API_KEY, fb_fields([
        'userId' => $uid, 'title' => '__debug_noauth__',
    ]));

    // 4. Try reading (GET) to see if read works
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

    // Cleanup any test docs created
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
            // Get raw error for debugging
            $rawRes = fb_call('POST', FB_FIRESTORE . '/tasks', fb_fields($data));
            echo json_encode(['ok'=>false,'msg'=>'Failed to save task.','firebase_error'=>$rawRes]);
        } else {
            echo json_encode(['ok'=>true,'id'=>$newId]);
        }
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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
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
    .hero-banner-text {}
    .hero-eyebrow {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: .12em;
      color: rgba(255,255,255,.5);
      margin-bottom: 8px;
    }
    .hero-title {
      font-family: var(--font-display);
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
  </style>
</head>
<body>

  <header class="header">
    <div class="brand">
      <div class="logo">AssTracker</div>
      <div class="logo-sub">Universal Assignment Tracker</div>
    </div>
    <div class="header-actions">
      <span class="header-user">Hi, <?= $userName ?></span>
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
  </script>
</body>
</html>