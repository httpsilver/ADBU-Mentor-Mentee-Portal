<?php
require_once '../php/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'mentor') { header('Location: mentee-tasks.php'); exit; }
$pageTitle   = 'Tasks';
$currentPage = 'tasks';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?> – ADBU MentorConnect</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    .task-card { background:var(--card); border:1.5px solid var(--border); border-radius:var(--radius); padding:20px; transition:var(--transition); }
    .task-card:hover { border-color:var(--accent); box-shadow:var(--shadow-md); transform:translateY(-1px); }
    .task-card.overdue { border-left:4px solid var(--danger); }
    .task-status-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:99px; font-size:11px; font-weight:600; }
    .pill-submitted { background:#FEF3C7; color:#92400E; }
    .pill-graded { background:#D1FAE5; color:#065F46; }
    .pill-not-submitted { background:#F3F4F6; color:#6B7280; }
    .pill-overdue { background:#FEE2E2; color:#991B1B; }
    .sub-row { display:flex; align-items:center; gap:12px; padding:12px; border-radius:var(--radius-sm); border:1px solid var(--border); background:var(--bg); margin-bottom:8px; }
    .grade-input { width:80px; padding:6px 10px; border:1.5px solid var(--border); border-radius:var(--radius-sm); font-family:Inter,sans-serif; font-size:14px; background:var(--card); color:var(--text); }
    .grade-input:focus { border-color:var(--accent); outline:none; }
    .attach-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:var(--bg); border:1px solid var(--border); border-radius:20px; font-size:12px; color:var(--text-muted); text-decoration:none; transition:var(--transition); }
    .attach-chip:hover { background:var(--accent-light); color:var(--accent); border-color:var(--accent); }
    .tab-btn { padding:8px 18px; border:none; background:none; font-size:14px; font-weight:500; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; transition:var(--transition); font-family:Inter,sans-serif; }
    .tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); font-weight:600; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar_nav.php'; ?>

    <!-- Header -->
    <div class="page-header-row mb-4">
      <div class="page-header" style="margin:0">
        <h1>📋 Tasks</h1>
        <p>Create and manage assignments for your mentees</p>
      </div>
      <button class="btn-primary-custom" data-modal-open="createTaskModal">
        <i class="bi bi-plus-lg"></i> Create Task
      </button>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon indigo"><i class="bi bi-clipboard-check"></i></div><div><div class="stat-value" id="sTotalTasks">—</div><div class="stat-label">Total Tasks</div></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value" id="sPendingReview">—</div><div class="stat-label">Pending Review</div></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div><div class="stat-value" id="sGraded">—</div><div class="stat-label">Graded</div></div></div>
      </div>
    </div>

    <!-- Filter tabs + mentee filter -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;border-bottom:1px solid var(--border)">
      <div style="display:flex;gap:0">
        <button class="tab-btn active" data-filter="all">All Tasks</button>
        <button class="tab-btn" data-filter="pending">Pending</button>
        <button class="tab-btn" data-filter="submitted">Submitted</button>
        <button class="tab-btn" data-filter="graded">Graded</button>
      </div>
      <select id="menteeFilter" class="form-control-custom" style="width:auto;min-width:180px">
        <option value="">All Mentees</option>
      </select>
    </div>

    <!-- Task list -->
    <div id="taskList">
      <div class="empty-state"><div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div><p>Loading tasks...</p></div>
    </div>



<!-- Create Task Modal  -->
<div class="modal-overlay" id="createTaskModal">
  <div class="modal-box" style="max-width:600px">
    <div class="modal-header">
      <h2 class="modal-title"><i class="bi bi-plus-circle me-2" style="color:var(--accent)"></i>Create New Task</h2>
      <button class="modal-close" data-modal-close="createTaskModal">×</button>
    </div>
    <div id="createTaskAlert" style="display:none;margin-bottom:16px"></div>
    <div class="form-group">
      <label class="form-label">Assign To <span style="color:var(--danger)">*</span></label>
      <select id="ctMentee" class="form-control-custom">
        <option value="">Select a mentee...</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Task Title <span style="color:var(--danger)">*</span></label>
      <input type="text" id="ctTitle" class="form-control-custom" placeholder="e.g., Research Paper on Machine Learning">
    </div>
    <div class="form-group">
      <label class="form-label">Description / Instructions</label>
      <textarea id="ctDescription" class="form-control-custom" rows="4" placeholder="Provide detailed instructions, requirements, or context for this task..." style="resize:vertical"></textarea>
    </div>
    <div class="row g-3">
      <div class="col-6">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Due Date</label>
          <input type="datetime-local" id="ctDueDate" class="form-control-custom">
        </div>
      </div>
      <div class="col-6">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Points / Marks</label>
          <input type="number" id="ctPoints" class="form-control-custom" placeholder="e.g., 100" min="0" max="1000">
        </div>
      </div>
    </div>
    <div class="form-group" style="margin-top:16px">
      <label class="form-label">Attach Resources (optional)</label>
      <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:16px;text-align:center;cursor:pointer;transition:var(--transition)" id="attachDropZone" onclick="document.getElementById('ctFiles').click()">
        <i class="bi bi-cloud-upload" style="font-size:24px;color:var(--text-muted)"></i>
        <div style="font-size:13px;color:var(--text-muted);margin-top:4px">Click or drag files here (PDF, Word, images…)</div>
        <input type="file" id="ctFiles" multiple style="display:none">
      </div>
      <div id="ctFileList" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px"></div>
    </div>
    <div class="modal-footer">
      <button class="btn-ghost-custom" data-modal-close="createTaskModal">Cancel</button>
      <button class="btn-primary-custom" id="createTaskBtn"><i class="bi bi-plus"></i> Create Task</button>
    </div>
  </div>
</div>

<!-- Task Detail / Grade Modal -->
<div class="modal-overlay" id="taskDetailModal">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-header">
      <h2 class="modal-title" id="tdTitle">Task Detail</h2>
      <button class="modal-close" data-modal-close="taskDetailModal">×</button>
    </div>
    <div id="taskDetailBody"></div>
  </div>
</div>

<!--  Edit Task Modal  -->
<div class="modal-overlay" id="editTaskModal">
  <div class="modal-box" style="max-width:540px">
    <div class="modal-header">
      <h2 class="modal-title">Edit Task</h2>
      <button class="modal-close" data-modal-close="editTaskModal">×</button>
    </div>
    <input type="hidden" id="etId">
    <div class="form-group">
      <label class="form-label">Title</label>
      <input type="text" id="etTitle" class="form-control-custom">
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea id="etDescription" class="form-control-custom" rows="4" style="resize:vertical"></textarea>
    </div>
    <div class="row g-3">
      <div class="col-6">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Due Date</label>
          <input type="datetime-local" id="etDueDate" class="form-control-custom">
        </div>
      </div>
      <div class="col-6">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Points</label>
          <input type="number" id="etPoints" class="form-control-custom" min="0">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-ghost-custom" data-modal-close="editTaskModal">Cancel</button>
      <button class="btn-primary-custom" id="saveEditBtn"><i class="bi bi-check2"></i> Save Changes</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
let allTasks = [];
let myMentees = [];
let currentFilter = 'all';

// Load stats 
async function loadStats() {
  const res = await API.get('../php/tasks.php?action=get_stats');
  if (res.success) {
    document.getElementById('sTotalTasks').textContent    = res.stats.total;
    document.getElementById('sPendingReview').textContent = res.stats.pending_review;
    document.getElementById('sGraded').textContent        = res.stats.graded;
  }
}

// Load mentees (for filters + dropdowns)
async function loadMentees() {
  const res = await API.get('../php/tasks.php?action=get_my_mentees');
  if (res.success) {
    myMentees = res.mentees;
    const ctSelect = document.getElementById('ctMentee');
    const filterSel = document.getElementById('menteeFilter');
    myMentees.forEach(m => {
      ctSelect.insertAdjacentHTML('beforeend', `<option value="${m.id}">${m.full_name}</option>`);
      filterSel.insertAdjacentHTML('beforeend', `<option value="${m.id}">${m.full_name}</option>`);
    });
  }
}

// Load tasks
async function loadTasks(menteeId = '') {
  const url = `../php/tasks.php?action=get_tasks${menteeId ? '&mentee_id='+menteeId : ''}`;
  const res = await API.get(url);
  if (res.success) {
    allTasks = res.tasks;
    renderTasks(filterTasks(allTasks));
  }
}

function filterTasks(tasks) {
  return tasks.filter(t => {
    if (currentFilter === 'pending') return !t.sub_status || t.sub_status === 'not_submitted';
    if (currentFilter === 'submitted') return t.sub_status === 'submitted';
    if (currentFilter === 'graded') return t.sub_status === 'graded';
    return true;
  });
}

function renderTasks(tasks) {
  const el = document.getElementById('taskList');
  if (!tasks.length) {
    el.innerHTML = `<div class="card-custom"><div class="empty-state"><i class="bi bi-clipboard-x"></i><p>No tasks found. Create your first task!</p></div></div>`;
    return;
  }
  el.innerHTML = tasks.map(t => {
    const overdue = t.is_overdue;
    const status  = t.sub_status || 'not_submitted';
    const statusHtml = {
      'not_submitted': `<span class="task-status-pill ${overdue ? 'pill-overdue' : 'pill-not-submitted'}"><i class="bi bi-clock"></i>${overdue ? 'Overdue' : 'Not Submitted'}</span>`,
      'submitted':     `<span class="task-status-pill pill-submitted"><i class="bi bi-send-check"></i>Submitted</span>`,
      'graded':        `<span class="task-status-pill pill-graded"><i class="bi bi-check-circle"></i>Graded${t.grade !== null ? ' · '+t.grade+(t.points?'/'+t.points:'')+'pts' : ''}</span>`,
    }[status] || '';
    const attachHtml = (t.attachments||[]).map(a => `
      <a href="../php/tasks.php?action=download_attachment&attach_id=${a.id}" class="attach-chip" download>
        <i class="bi bi-paperclip"></i>${escapeHtml(a.file_name)}
      </a>`).join('');
    return `
      <div class="task-card mb-3 animate-in ${overdue ? 'overdue' : ''}" onclick="openTaskDetail(${t.id})">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
              <h3 style="font-size:15px;font-weight:700;margin:0">${escapeHtml(t.title)}</h3>
              ${statusHtml}
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
              <img src="${t.avatar}" class="avatar" style="width:22px;height:22px" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(t.mentee_name)}&background=4F46E5&color=fff'">
              <span style="font-size:13px;color:var(--text-muted)">${escapeHtml(t.mentee_name)}</span>
              ${t.due_date ? `<span style="font-size:12px;color:${overdue?'var(--danger)':'var(--text-muted)'}"><i class="bi bi-calendar${overdue?'-x':''} me-1"></i>${new Date(t.due_date).toLocaleDateString([], {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})}</span>` : ''}
              ${t.points ? `<span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-star me-1"></i>${t.points} pts</span>` : ''}
            </div>
            ${t.description ? `<p style="font-size:13px;color:var(--text-muted);margin:0 0 8px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">${escapeHtml(t.description)}</p>` : ''}
            ${attachHtml ? `<div style="display:flex;flex-wrap:wrap;gap:6px">${attachHtml}</div>` : ''}
          </div>
          <div style="display:flex;gap:6px;flex-shrink:0" onclick="event.stopPropagation()">
            <button class="btn-icon" title="Edit" onclick="openEditModal(${t.id})"><i class="bi bi-pencil"></i></button>
            <button class="btn-icon" title="Delete" style="color:var(--danger)" onclick="deleteTask(${t.id}, '${escapeHtml(t.title)}')"><i class="bi bi-trash"></i></button>
          </div>
        </div>
      </div>`;
  }).join('');
}

// Task Detail / Grade 
async function openTaskDetail(taskId) {
  Modal.open('taskDetailModal');
  const body = document.getElementById('taskDetailBody');
  body.innerHTML = `<div class="empty-state"><div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div></div>`;
  const res = await API.get(`../php/tasks.php?action=get_task&task_id=${taskId}`);
  if (!res.success) { body.innerHTML = `<p style="color:var(--danger)">Failed to load task.</p>`; return; }
  const t = res.task;
  document.getElementById('tdTitle').textContent = t.title;

  const attachHtml = (t.attachments||[]).map(a => `
    <a href="../php/tasks.php?action=download_attachment&attach_id=${a.id}" class="attach-chip" download>
      <i class="bi bi-paperclip"></i>${escapeHtml(a.file_name)} <span style="opacity:0.6">(${formatBytes(a.file_size)})</span>
    </a>`).join('');

  const subsHtml = (t.submissions||[]).map(s => {
    const st = s.status;
    return `
      <div class="sub-row">
        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(s.full_name)}&background=4F46E5&color=fff" class="avatar avatar-sm">
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:14px">${escapeHtml(s.full_name)}</div>
          <div style="font-size:12px;color:var(--text-muted)">${st === 'submitted' ? 'Submitted '+formatTime(s.submitted_at) : st === 'graded' ? 'Graded' : st === 'not_submitted' ? 'Not submitted yet' : st}</div>
          ${s.submission_text ? `<div style="font-size:12px;color:var(--text-muted);margin-top:3px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">"${escapeHtml(s.submission_text)}"</div>` : ''}
          ${s.file_name ? `<a href="../php/tasks.php?action=download_submission&sub_id=${s.id}" class="attach-chip" download style="margin-top:4px"><i class="bi bi-file-earmark-arrow-down"></i>${escapeHtml(s.file_name)}</a>` : ''}
        </div>
        ${(st === 'submitted' || st === 'graded') ? `
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
          <div style="display:flex;align-items:center;gap:6px">
            <input type="number" class="grade-input" id="grade_${s.id}" value="${s.grade !== null ? s.grade : ''}" placeholder="${t.points || '—'}" min="0" max="${t.points || 999}">
            <span style="font-size:13px;color:var(--text-muted)">/ ${t.points || '—'}</span>
          </div>
          <input type="text" class="form-control-custom" id="feedback_${s.id}" value="${s.feedback || ''}" placeholder="Feedback..." style="font-size:12px;padding:5px 8px;width:180px">
          <button class="btn-primary-custom" style="padding:6px 12px;font-size:12px" onclick="gradeSubmission(${t.id}, ${s.mentee_id}, '${s.id}')">
            <i class="bi bi-check2"></i> ${st === 'graded' ? 'Update Grade' : 'Grade'}
          </button>
        </div>` : ''}
      </div>`;
  }).join('');

  body.innerHTML = `
    <div style="margin-bottom:16px">
      ${t.description ? `<p style="font-size:14px;color:var(--text-muted);line-height:1.6">${escapeHtml(t.description)}</p>` : ''}
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:12px">
        ${t.due_date ? `<span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-calendar me-1"></i>${new Date(t.due_date).toLocaleString()}</span>` : ''}
        ${t.points ? `<span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-star me-1"></i>${t.points} points</span>` : ''}
      </div>
      ${attachHtml ? `<div style="margin-top:12px"><div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:6px">RESOURCES</div><div style="display:flex;flex-wrap:wrap;gap:6px">${attachHtml}</div></div>` : ''}
    </div>
    <div style="border-top:1px solid var(--border);padding-top:16px">
      <h4 style="font-size:14px;font-weight:700;margin-bottom:12px">Submissions</h4>
      ${subsHtml || '<div class="empty-state" style="padding:20px"><i class="bi bi-inbox"></i><p>No submissions yet.</p></div>'}
    </div>`;
}

async function gradeSubmission(taskId, menteeId, subId) {
  const grade    = document.getElementById('grade_' + subId)?.value;
  const feedback = document.getElementById('feedback_' + subId)?.value;
  const res = await API.post('../php/tasks.php?action=grade_submission', { task_id: taskId, mentee_id: menteeId, grade, feedback });
  if (res.success) { Toast.success(res.message); loadTasks(); loadStats(); Modal.close('taskDetailModal'); }
  else Toast.error(res.message);
}

// Create task 
const ctFiles = document.getElementById('ctFiles');
ctFiles.addEventListener('change', () => {
  const list = document.getElementById('ctFileList');
  list.innerHTML = [...ctFiles.files].map((f, i) => `
    <span class="attach-chip" style="cursor:default"><i class="bi bi-file-earmark"></i>${escapeHtml(f.name)} <span style="opacity:0.6">${formatBytes(f.size)}</span></span>`).join('');
});
const dropZone = document.getElementById('attachDropZone');
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--accent)'; });
dropZone.addEventListener('dragleave', () => dropZone.style.borderColor = '');
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.style.borderColor = '';
  const dt = new DataTransfer();
  [...e.dataTransfer.files].forEach(f => dt.items.add(f));
  ctFiles.files = dt.files;
  ctFiles.dispatchEvent(new Event('change'));
});

document.getElementById('createTaskBtn').addEventListener('click', async () => {
  const btn   = document.getElementById('createTaskBtn');
  const alert = document.getElementById('createTaskAlert');
  const menteeId = document.getElementById('ctMentee').value;
  const title    = document.getElementById('ctTitle').value.trim();
  if (!menteeId || !title) {
    alert.style.display='flex'; alert.className='alert-custom alert-error';
    alert.innerHTML='<i class="bi bi-x-circle me-2"></i>Please select a mentee and enter a title.'; return;
  }
  setButtonLoading(btn, true, 'Creating...');
  const formData = new FormData();
  formData.append('mentee_id',    menteeId);
  formData.append('title',        title);
  formData.append('description',  document.getElementById('ctDescription').value.trim());
  formData.append('due_date',     document.getElementById('ctDueDate').value);
  formData.append('points',       document.getElementById('ctPoints').value);
  [...ctFiles.files].forEach(f => formData.append('attachments[]', f));

  const res = await fetch('../php/tasks.php?action=create_task', { method:'POST', body: formData });
  const data = await res.json();
  setButtonLoading(btn, false);
  if (data.success) {
    Toast.success(data.message);
    Modal.close('createTaskModal');
    ['ctMentee','ctTitle','ctDescription','ctDueDate','ctPoints'].forEach(id => document.getElementById(id).value='');
    document.getElementById('ctFileList').innerHTML='';
    loadTasks(); loadStats();
  } else {
    alert.style.display='flex'; alert.className='alert-custom alert-error';
    alert.innerHTML=`<i class="bi bi-x-circle me-2"></i>${data.message}`;
  }
});

//  Edit task 
function openEditModal(taskId) {
  const t = allTasks.find(t => t.id == taskId);
  if (!t) return;
  document.getElementById('etId').value          = t.id;
  document.getElementById('etTitle').value       = t.title;
  document.getElementById('etDescription').value = t.description || '';
  document.getElementById('etDueDate').value     = t.due_date ? t.due_date.replace(' ','T').slice(0,16) : '';
  document.getElementById('etPoints').value      = t.points || '';
  Modal.open('editTaskModal');
}

document.getElementById('saveEditBtn').addEventListener('click', async () => {
  const btn = document.getElementById('saveEditBtn');
  setButtonLoading(btn, true, 'Saving...');
  const res = await API.post('../php/tasks.php?action=update_task', {
    task_id:     document.getElementById('etId').value,
    title:       document.getElementById('etTitle').value,
    description: document.getElementById('etDescription').value,
    due_date:    document.getElementById('etDueDate').value,
    points:      document.getElementById('etPoints').value,
  });
  setButtonLoading(btn, false);
  if (res.success) { Toast.success(res.message); Modal.close('editTaskModal'); loadTasks(); }
  else Toast.error(res.message);
});

//  Delete task 
function deleteTask(id, title) {
  confirmAction(`Delete task "${title}"? All submissions will also be deleted.`, async () => {
    const res = await API.post('../php/tasks.php?action=delete_task', { task_id: id });
    if (res.success) { Toast.success(res.message); loadTasks(); loadStats(); }
    else Toast.error(res.message);
  });
}

// Tabs & filter
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    renderTasks(filterTasks(allTasks));
  });
});
document.getElementById('menteeFilter').addEventListener('change', e => loadTasks(e.target.value));

function escapeHtml(t) { const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
function formatBytes(b) { if(!b)return''; if(b<1024)return b+'B'; if(b<1048576)return(b/1024).toFixed(1)+'KB'; return(b/1048576).toFixed(1)+'MB'; }

loadStats(); loadMentees(); loadTasks();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
