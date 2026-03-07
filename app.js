'use strict';

// ── Constants ─────────────────────────────────────────────
const PRIORITY_COLOR = {
  Low:    '#4ade80',
  Medium: '#facc15',
  High:   '#fb923c',
  Urgent: '#f87171',
};

const STATUS_COLOR = {
  'To Do':       '#94a3b8',
  'In Progress': '#60a5fa',
  Done:          '#4ade80',
};

const STATUSES = ['To Do', 'In Progress', 'Done'];

// ── State ─────────────────────────────────────────────────
let viewMode        = 'board';
let activeTab       = 'active';   // 'active' | 'completed'
let pendingDeleteId = null;

// ── DOM helpers ───────────────────────────────────────────
const $   = id => document.getElementById(id);
const esc = s  => String(s)
  .replace(/&/g,  '&amp;')
  .replace(/</g,  '&lt;')
  .replace(/>/g,  '&gt;')
  .replace(/"/g,  '&quot;');

// ── HTTP helpers ──────────────────────────────────────────
async function post(data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const r = await fetch('', { method: 'POST', body: fd });
  return r.json();
}

async function get(params) {
  const qs = new URLSearchParams(params).toString();
  const r  = await fetch('?' + qs);
  return r.json();
}

// ── Toast ─────────────────────────────────────────────────
function showToast(msg) {
  const el = $('toast');
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2400);
}

// ── Date formatting ───────────────────────────────────────
function fmtDate(d) {
  if (!d) return 'No deadline';
  const date = new Date(d + 'T00:00:00');
  const now  = new Date();
  now.setHours(0, 0, 0, 0);
  const diff = (date - now) / 86_400_000;
  if (diff < 0) return 'Overdue';
  if (diff < 1) return 'Due Today';
  if (diff < 2) return 'Due Tomorrow';
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function isOverdue(d, status) {
  if (!d || status === 'Done') return false;
  const now = new Date(); now.setHours(0, 0, 0, 0);
  return new Date(d + 'T00:00:00') < now;
}

// ── Mark Complete ─────────────────────────────────────────
async function markComplete(id) {
  await post({ action: 'complete', id: id });
  showToast('Task marked as complete! ✓');
  refresh();
}

// ── Stats ─────────────────────────────────────────────────
async function loadStats() {
  const s = await get({ action: 'stats' });
  const cfg = [
    { label: 'Total Tasks', value: s.total,      color: '#818cf8', abbr: 'ALL'  },
    { label: 'In Progress', value: s.inProgress, color: '#60a5fa', abbr: 'WIP'  },
    { label: 'Completed',   value: s.done,        color: '#4ade80', abbr: 'DONE' },
    { label: 'Overdue',     value: s.overdue,     color: '#f87171', abbr: 'LATE' },
  ];
  $('statsGrid').innerHTML = cfg.map(c => {
    const pct = s.total ? Math.round((c.value / s.total) * 100) : 0;
    return '<div class="stat-card">'
      + '<div class="stat-header"><div>'
      + '<div class="stat-label">' + c.label + '</div>'
      + '<div class="stat-value" style="color:' + c.color + '">' + c.value + '</div>'
      + '</div>'
      + '<div class="stat-icon" style="background:' + c.color + '22;color:' + c.color + '">' + c.abbr + '</div>'
      + '</div>'
      + '<div class="stat-bar"><div class="stat-fill" style="width:' + pct + '%;background:' + c.color + '"></div></div>'
      + '</div>';
  }).join('');

  // Update completed tab badge
  const badge = $('completedBadge');
  if (badge) badge.textContent = s.done;
}

// ── Task list ─────────────────────────────────────────────
async function loadTasks() {
  const params = {
    action:   'list',
    category: $('fCategory').value,
    status:   $('fStatus').value,
    priority: $('fPriority').value,
    q:        $('searchInput').value,
    sort:     $('fSort').value,
  };
  const tasks = await get(params);

  // Split into active vs completed
  const activeTasks    = tasks.filter(t => t.status !== 'Done');
  const completedTasks = tasks.filter(t => t.status === 'Done');

  if (activeTab === 'completed') {
    $('taskCount').textContent = completedTasks.length + ' completed task' + (completedTasks.length !== 1 ? 's' : '');
    renderCompleted(completedTasks);
    $('boardView').classList.add('hidden');
    $('listView').classList.add('hidden');
    $('completedView').classList.remove('hidden');
  } else {
    $('taskCount').textContent = activeTasks.length + ' task' + (activeTasks.length !== 1 ? 's' : '');
    $('completedView').classList.add('hidden');
    if (viewMode === 'board') {
      $('boardView').classList.remove('hidden');
      $('listView').classList.add('hidden');
      renderBoard(activeTasks);
    } else {
      $('listView').classList.remove('hidden');
      $('boardView').classList.add('hidden');
      renderList(activeTasks);
    }
  }
}

// ── Board renderer ────────────────────────────────────────
function renderBoard(tasks) {
  // Only show To Do and In Progress on board when in active tab
  const boardStatuses = ['To Do', 'In Progress'];
  $('boardView').innerHTML = boardStatuses.map(function(status) {
    const cols = tasks.filter(function(t) { return t.status === status; });
    var cards = cols.length ? cols.map(cardHTML).join('') : '<div class="empty-col">No tasks</div>';
    return '<div class="board-col">'
      + '<div class="col-header">'
      + '<div class="col-dot" style="background:' + STATUS_COLOR[status] + '"></div>'
      + '<span class="col-title">' + status + '</span>'
      + '<span class="col-count">' + cols.length + '</span>'
      + '</div>'
      + '<div class="col-body">' + cards + '</div>'
      + '</div>';
  }).join('');
}

// ── List renderer ─────────────────────────────────────────
function renderList(tasks) {
  $('listView').innerHTML = tasks.length
    ? tasks.map(rowHTML).join('')
    : '<div class="empty-col" style="padding:60px">No tasks match your filters</div>';
}

// ── Completed renderer ────────────────────────────────────
function renderCompleted(tasks) {
  if (!tasks.length) {
    $('completedView').innerHTML = '<div class="completed-empty">'
      + '<div class="completed-empty-icon">🎉</div>'
      + '<div class="completed-empty-title">No completed tasks yet</div>'
      + '<div class="completed-empty-sub">Tasks you mark as complete will appear here.</div>'
      + '</div>';
    return;
  }

  $('completedView').innerHTML = '<div class="completed-list">'
    + tasks.map(completedRowHTML).join('')
    + '</div>';
}

// ── Card HTML ─────────────────────────────────────────────
function cardHTML(t) {
  const od = isOverdue(t.deadline, t.status);
  const pc = PRIORITY_COLOR[t.priority] || '#aaa';
  const dl = fmtDate(t.deadline);
  return '<div class="task-card' + (od ? ' overdue' : '') + '">'
    + '<div class="card-top">'
    + '<span class="card-category">' + esc(t.category) + '</span>'
    + '<span class="priority-badge" style="background:' + pc + '22;color:' + pc + '">' + esc(t.priority) + '</span>'
    + '</div>'
    + '<div class="card-title">' + esc(t.title) + '</div>'
    + (t.description ? '<div class="card-desc">' + esc(t.description) + '</div>' : '')
    + '<div class="card-meta">'
    + '<span class="card-deadline' + (od ? ' late' : '') + '">' + dl + '</span>'
    + (t.assigned_to ? '<span class="card-assigned">' + esc(t.assigned_to) + '</span>' : '')
    + '</div>'
    + '<div class="card-actions">'
    + '<button type="button" class="btn-sm btn-complete" onclick="markComplete(\'' + t.id + '\')">✓ Done</button>'
    + '<button type="button" class="btn-sm btn-edit" onclick="editTask(\'' + t.id + '\')">Edit</button>'
    + '<button type="button" class="btn-sm btn-delete" onclick="openDeleteModal(\'' + t.id + '\')">Delete</button>'
    + '</div>'
    + '</div>';
}

// ── Row HTML ──────────────────────────────────────────────
function rowHTML(t) {
  const od = isOverdue(t.deadline, t.status);
  const pc = PRIORITY_COLOR[t.priority] || '#aaa';
  const sc = STATUS_COLOR[t.status]    || '#aaa';
  const dl = fmtDate(t.deadline);
  return '<div class="task-row' + (od ? ' overdue' : '') + '">'
    + '<span class="row-category">' + esc(t.category) + '</span>'
    + '<div class="row-info">'
    + '<div class="row-title">' + esc(t.title) + '</div>'
    + (t.description ? '<div class="row-desc">' + esc(t.description) + '</div>' : '')
    + '</div>'
    + '<div class="row-meta">'
    + '<span class="priority-badge" style="background:' + pc + '22;color:' + pc + '">' + esc(t.priority) + '</span>'
    + '<span class="row-status" style="color:' + sc + '">' + esc(t.status) + '</span>'
    + '<span class="row-deadline' + (od ? ' late' : '') + '">' + dl + '</span>'
    + (t.assigned_to ? '<span class="row-assigned">' + esc(t.assigned_to) + '</span>' : '')
    + '<button type="button" class="btn-sm btn-complete" onclick="markComplete(\'' + t.id + '\')">✓ Done</button>'
    + '<button type="button" class="btn-sm btn-edit" onclick="editTask(\'' + t.id + '\')">Edit</button>'
    + '<button type="button" class="btn-sm btn-delete" onclick="openDeleteModal(\'' + t.id + '\')">Delete</button>'
    + '</div>'
    + '</div>';
}

// ── Completed row HTML ────────────────────────────────────
function completedRowHTML(t) {
  const pc = PRIORITY_COLOR[t.priority] || '#aaa';
  const dl = t.deadline
    ? new Date(t.deadline + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : 'No deadline';
  return '<div class="completed-row">'
    + '<div class="completed-check">✓</div>'
    + '<span class="row-category">' + esc(t.category) + '</span>'
    + '<div class="row-info">'
    + '<div class="completed-title">' + esc(t.title) + '</div>'
    + (t.description ? '<div class="row-desc">' + esc(t.description) + '</div>' : '')
    + '</div>'
    + '<div class="row-meta">'
    + '<span class="priority-badge" style="background:' + pc + '22;color:' + pc + '">' + esc(t.priority) + '</span>'
    + '<span class="completed-date">' + dl + '</span>'
    + (t.assigned_to ? '<span class="row-assigned">' + esc(t.assigned_to) + '</span>' : '')
    + '<button type="button" class="btn-sm btn-undo" onclick="undoComplete(\'' + t.id + '\')">↩ Undo</button>'
    + '<button type="button" class="btn-sm btn-delete" onclick="openDeleteModal(\'' + t.id + '\')">Delete</button>'
    + '</div>'
    + '</div>';
}

// ── Undo complete ─────────────────────────────────────────
async function undoComplete(id) {
  await post({ action: 'undo_complete', id: id });
  showToast('Task moved back to In Progress.');
  refresh();
}

// ── Delete modal ──────────────────────────────────────────
function openDeleteModal(id) {
  pendingDeleteId = id;
  $('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
  pendingDeleteId = null;
  $('deleteModal').classList.add('hidden');
}

async function confirmDelete() {
  if (!pendingDeleteId) return;
  var id = pendingDeleteId;
  closeDeleteModal();
  await post({ action: 'delete', id: id });
  showToast('Task deleted.');
  refresh();
}

// ── Edit task ─────────────────────────────────────────────
async function editTask(id) {
  const t = await get({ action: 'get', id: id });
  $('taskId').value    = t.id           || '';
  $('fTitle').value    = t.title        || '';
  $('fDesc').value     = t.description  || '';
  $('fCat').value      = t.category     || 'Work';
  $('fPri').value      = t.priority     || 'Medium';
  $('fStat').value     = t.status       || 'To Do';
  $('fDeadline').value = t.deadline     || '';
  $('fAssigned').value = t.assigned_to  || '';
  $('modalTitle').textContent = 'Edit Task';
  $('overlay').classList.remove('hidden');
}

// ── New task modal ────────────────────────────────────────
function openModal() {
  $('taskId').value    = '';
  $('fTitle').value    = '';
  $('fDesc').value     = '';
  $('fCat').value      = 'Work';
  $('fPri').value      = 'Medium';
  $('fStat').value     = 'To Do';
  $('fDeadline').value = '';
  $('fAssigned').value = '';
  $('modalTitle').textContent = 'New Task';
  $('overlay').classList.remove('hidden');
}

function closeModal() {
  $('overlay').classList.add('hidden');
}

// ── Save task ─────────────────────────────────────────────
async function saveTask() {
  const title = $('fTitle').value.trim();
  if (!title) { showToast('Title is required.'); return; }

  const res = await post({
    action:      'save',
    id:          $('taskId').value || 0,
    title:       title,
    description: $('fDesc').value,
    category:    $('fCat').value,
    priority:    $('fPri').value,
    status:      $('fStat').value,
    deadline:    $('fDeadline').value,
    assigned_to: $('fAssigned').value,
  });

  if (res.ok) {
    closeModal();
    showToast('Task saved.');
    refresh();
  } else {
    const detail = res.firebase_error
      ? JSON.stringify(res.firebase_error, null, 2)
      : (res.msg || 'Unknown error');
    showToast('Save failed: ' + (res.msg || 'Check Firebase rules.'));
    console.error('Firebase error:', detail);
  }
}

function refresh() {
  loadStats();
  loadTasks();
}

// ── Tab switching ─────────────────────────────────────────
function switchTab(tab) {
  activeTab = tab;
  // Update tab styles
  document.querySelectorAll('.tab-btn').forEach(function(btn) {
    btn.classList.toggle('tab-active', btn.dataset.tab === tab);
  });
  // Show/hide toggle view button (not relevant in completed tab)
  const toggleBtn = $('toggleView');
  if (toggleBtn) {
    toggleBtn.style.display = tab === 'completed' ? 'none' : '';
  }
  loadTasks();
}

// ── Event listeners ───────────────────────────────────────
$('toggleView').addEventListener('click', function () {
  viewMode = viewMode === 'board' ? 'list' : 'board';
  $('boardView').classList.toggle('hidden', viewMode !== 'board');
  $('listView').classList.toggle('hidden',  viewMode !== 'list');
  this.textContent = viewMode === 'board' ? 'List View' : 'Board View';
  loadTasks();
});

$('btnNewTask').addEventListener('click', openModal);
$('btnCancel').addEventListener('click',  closeModal);
$('btnSave').addEventListener('click',    saveTask);

$('overlay').addEventListener('click', function (e) {
  if (e.target === this) closeModal();
});

$('deleteModal').addEventListener('click', function (e) {
  if (e.target === this) closeDeleteModal();
});
$('btnCancelDelete').addEventListener('click', closeDeleteModal);
$('btnConfirmDelete').addEventListener('click', confirmDelete);

['fCategory', 'fStatus', 'fPriority', 'fSort'].forEach(function(id) {
  $(id).addEventListener('change', loadTasks);
});

var searchTimer;
$('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadTasks, 300);
});

// Tab buttons
document.querySelectorAll('.tab-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    switchTab(this.dataset.tab);
  });
});

// ── Init ──────────────────────────────────────────────────
refresh();