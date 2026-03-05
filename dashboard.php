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

// ── Helper: redirect ──────────────────────────────────────
function redirect(string $url): void { header("Location: $url"); exit; }

function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ═══════════════════════════════════════════════════════════
//  JSON API  (XHR requests from app.js)
// ═══════════════════════════════════════════════════════════
$action = $_POST['action'] ?? $_GET['action'] ?? '';

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
        fb_firestore_update("tasks/$id", $data, $idToken);
        echo json_encode(['ok'=>true,'id'=>$id]);
    } else {
        $newId = fb_firestore_add('tasks', $data, $idToken);
        echo json_encode(['ok'=>true,'id'=>$newId]);
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

    // PHP-side filtering
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

    // PHP-side sorting
    $sort = $_GET['sort'] ?? 'deadline';
    $pOrd = ['Urgent'=>1,'High'=>2,'Medium'=>3,'Low'=>4];
    usort($tasks, function($a, $b) use ($sort, $pOrd) {
        if ($sort === 'priority') return ($pOrd[$a['priority']]??9) <=> ($pOrd[$b['priority']]??9);
        if ($sort === 'title')    return strcasecmp($a['title']??'', $b['title']??'');
        // deadline: empty last
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
    $tasks  = fb_firestore_query('tasks', [['userId','EQUAL',$uid]], $idToken);
    $now    = date('Y-m-d');
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
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header class="header">
    <div class="brand">
      <div class="logo">AssTracker</div>
      <div class="logo-sub">Universal Assignment Tracker</div>
    </div>
    <div class="header-actions">
      <span class="header-user">Hi, <?= $userName ?></span>
      <button class="btn btn-ghost" id="toggleView">List View</button>
      <button class="btn btn-primary" id="btnNewTask">+ New Task</button>
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
    <div class="stats" id="statsGrid"></div>

    <div class="filters">
      <input type="text" id="searchInput" placeholder="🔍  Search tasks or people…">
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
        <button class="btn btn-ghost" id="btnCancel">Cancel</button>
        <button class="btn btn-primary" id="btnSave">Save Task</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>
  <script src="app.js"></script>
</body>
</html>
