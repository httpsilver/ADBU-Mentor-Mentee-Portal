<?php
require_once '../php/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'mentee') { header('Location: mentor-tasks.php'); exit; }
$pageTitle   = 'My Tasks';
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
    .task-card { background:var(--card); border:1.5px solid var(--border); border-radius:var(--radius); padding:20px; transition:var(--transition); cursor:pointer; }
    .task-card:hover { border-color:var(--accent); box-shadow:var(--shadow-md); transform:translateY(-1px); }
    .task-card.overdue-card { border-left:4px solid var(--danger); }
    .task-card.submitted-card { border-left:4px solid var(--warning); }
    .task-card.graded-card { border-left:4px solid var(--success); }
    .status-badge { display:inline-flex; align-items:center; gap:5px; padding:4px 12px; border-radius:99px; font-size:12px; font-weight:600; }
    .sb-pending   { background:#F3F4F6; color:#6B7280; }
    .sb-overdue   { background:#FEE2E2; color:#991B1B; }
    .sb-submitted { background:#FEF3C7; color:#92400E; }
    .sb-graded    { background:#D1FAE5; color:#065F46; }
    .grade-circle { width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; flex-shrink:0; }
    .attach-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:var(--bg); border:1px solid var(--border); border-radius:20px; font-size:12px; color:var(--text-muted); text-decoration:none; transition:var(--transition); }
    .attach-chip:hover { background:var(--accent-light); color:var(--accent); border-color:var(--accent); }
    .tab-btn { padding:8px 18px; border:none; background:none; font-size:14px; font-weight:500; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; transition:var(--transition); font-family:Inter,sans-serif; }
    .tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); font-weight:600; }
  </style>
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar_nav.php'; ?>

    <div class="page-header"><h1>📋 My Tasks</h1><p>View and submit assignments from your mentor</p></div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon indigo"><i class="bi bi-clipboard-check"></i></div><div><div class="stat-value" id="sTotal">—</div><div class="stat-label">Total</div></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-value" id="sPending">—</div><div class="stat-label">Pending</div></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div><div class="stat-value" id="sSubmitted">—</div><div class="stat-label">Submitted</div></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon rose"><i class="bi bi-star"></i></div><div><div class="stat-value" id="sGraded">—</div><div class="stat-label">Graded</div></div></div>
      </div>
    </div>

    <!-- Tabs -->
    <div style="border-bottom:1px solid var(--border);margin-bottom:20px">
      <button class="tab-btn active" data-filter="all">All</button>
      <button class="tab-btn" data-filter="pending">Pending</button>
      <button class="tab-btn" data-filter="submitted">Submitted</button>
      <button class="tab-btn" data-filter="graded">Graded</button>
    </div>

    <div id="taskList">
      <div class="empty-state"><div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div><p>Loading tasks...</p></div>
    </div>

  </div>
</div>
</div>

<!-- Task Detail + Submit Modal -->
<div class="modal-overlay" id="taskModal">
  <div class="modal-box" style="max-width:600px">
    <div class="modal-header">
      <h2 class="modal-title" id="tmTitle">Task</h2>
      <button class="modal-close" data-modal-close="taskModal">×</button>
    </div>
    <div id="taskModalBody"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
let allTasks = [];
let currentFilter = 'all';

async function loadStats() {
  const res = await API.get('../php/tasks.php?action=get_stats');
  if (res.success) {
    document.getElementById('sTotal').textContent     = res.stats.total;
    document.getElementById('sPending').textContent   = res.stats.pending;
    document.getElementById('sSubmitted').textContent = res.stats.submitted;
    document.getElementById('sGraded').textContent    = res.stats.graded;
  }
}

async function loadTasks() {
  const res = await API.get('../php/tasks.php?action=get_tasks');
  const el  = document.getElementById('taskList');
  if (res.success) {
    allTasks = res.tasks;
    renderTasks(filterTasks(allTasks));
  }
}

function filterTasks(tasks) {
  return tasks.filter(t => {
    const st = t.sub_status || 'not_submitted';
    if (currentFilter === 'pending')   return st === 'not_submitted';
    if (currentFilter === 'submitted') return st === 'submitted';
    if (currentFilter === 'graded')    return st === 'graded';
    return true;
  });
}

function renderTasks(tasks) {
  const el = document.getElementById('taskList');
  if (!tasks.length) {
    el.innerHTML = `<div class="card-custom"><div class="empty-state"><i class="bi bi-clipboard-x"></i><p>No tasks here. Your mentor will assign tasks soon!</p></div></div>`;
    return;
  }
  el.innerHTML = tasks.map(t => {
    const st      = t.sub_status || 'not_submitted';
    const overdue = t.is_overdue;
    const cardClass = st === 'graded' ? 'graded-card' : st === 'submitted' ? 'submitted-card' : overdue ? 'overdue-card' : '';
    const badge = {
      'not_submitted': `<span class="status-badge ${overdue?'sb-overdue':'sb-pending'}"><i class="bi bi-clock"></i>${overdue?'Overdue':'Pending'}</span>`,
      'submitted':     `<span class="status-badge sb-submitted"><i class="bi bi-send-check"></i>Submitted</span>`,
      'graded':        `<span class="status-badge sb-graded"><i class="bi bi-star-fill"></i>Graded ${t.grade !== null ? t.grade + (t.points?'/'+t.points:'') + ' pts' : ''}</span>`,
    }[st] || '';
    const attachHtml = (t.attachments||[]).map(a =>
      `<a href="../php/tasks.php?action=download_attachment&attach_id=${a.id}" class="attach-chip" download onclick="event.stopPropagation()"><i class="bi bi-file-earmark-arrow-down"></i>${escapeHtml(a.file_name)}</a>`
    ).join('');
    return `
      <div class="task-card mb-3 animate-in ${cardClass}" onclick="openTask(${t.id})">
        <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
          <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
              <h3 style="font-size:15px;font-weight:700;margin:0">${escapeHtml(t.title)}</h3>
              ${badge}
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
              <span style="font-size:13px;color:var(--text-muted)"><i class="bi bi-person-badge me-1"></i>${escapeHtml(t.mentor_name)}</span>
              ${t.due_date ? `<span style="font-size:12px;color:${overdue?'var(--danger)':'var(--text-muted)'}"><i class="bi bi-calendar me-1"></i>Due: ${new Date(t.due_date).toLocaleDateString([],{month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'})}</span>` : ''}
              ${t.points ? `<span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-star me-1"></i>${t.points} pts</span>` : ''}
            </div>
            ${t.description ? `<p style="font-size:13px;color:var(--text-muted);margin:0 0 8px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">${escapeHtml(t.description)}</p>` : ''}
            ${attachHtml ? `<div style="display:flex;flex-wrap:wrap;gap:6px">${attachHtml}</div>` : ''}
          </div>
          ${st === 'graded' && t.grade !== null ? `
          <div class="grade-circle" style="background:${t.grade >= (t.points*0.7||70) ? '#D1FAE5' : '#FEE2E2'};color:${t.grade >= (t.points*0.7||70) ? '#065F46' : '#991B1B'}">
            ${t.grade}
          </div>` : ''}
        </div>
      </div>`;
  }).join('');
}

// Open Task Detail / Submit 
async function openTask(taskId) {
  Modal.open('taskModal');
  const body = document.getElementById('taskModalBody');
  body.innerHTML = `<div class="empty-state"><div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div></div>`;
  const res = await API.get(`../php/tasks.php?action=get_task&task_id=${taskId}`);
  if (!res.success) { body.innerHTML = `<p style="color:var(--danger)">Failed to load task.</p>`; return; }
  const t  = res.task;
  const st = res.task.my_submission;
  document.getElementById('tmTitle').textContent = t.title;

  const attachHtml = (t.attachments||[]).map(a => `
    <a href="../php/tasks.php?action=download_attachment&attach_id=${a.id}" class="attach-chip" download>
      <i class="bi bi-file-earmark-arrow-down"></i>${escapeHtml(a.file_name)} <span style="opacity:0.6">${formatBytes(a.file_size)}</span>
    </a>`).join('');

  let gradedHtml = '';
  if (st && st.status === 'graded') {
    const pct = t.points ? Math.round(st.grade / t.points * 100) : null;
    gradedHtml = `
      <div style="background:var(--accent-light);border-radius:var(--radius);padding:16px;margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:12px">
          <div class="grade-circle" style="width:52px;height:52px;background:${(pct||100)>=70?'#D1FAE5':'#FEE2E2'};color:${(pct||100)>=70?'#065F46':'#991B1B'}">
            ${st.grade !== null ? st.grade : '—'}
          </div>
          <div>
            <div style="font-weight:700;font-size:15px">${st.grade !== null ? st.grade : '—'}${t.points?' / '+t.points+' points':' points'}</div>
            ${pct !== null ? `<div style="font-size:12px;color:var(--text-muted)">${pct}%</div>` : ''}
          </div>
        </div>
        ${st.feedback ? `<div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);font-size:13px;color:var(--text-muted)"><strong>Feedback:</strong> ${escapeHtml(st.feedback)}</div>` : ''}
      </div>`;
  }

  let submissionHtml = '';
  if (st && (st.status === 'submitted' || st.status === 'graded')) {
    submissionHtml = `
      <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-bottom:16px;background:var(--bg)">
        <div style="font-size:12px;font-weight:600;color:var(--text-muted);margin-bottom:8px">YOUR SUBMISSION · ${formatTime(st.submitted_at)}</div>
        ${st.submission_text ? `<p style="font-size:13px;margin:0 0 8px">${escapeHtml(st.submission_text)}</p>` : ''}
        ${st.file_name ? `<a href="../php/tasks.php?action=download_submission&sub_id=${st.id}" class="attach-chip" download><i class="bi bi-file-earmark-check"></i>${escapeHtml(st.file_name)}</a>` : ''}
      </div>`;
  }

  const canSubmit = !st || st.status === 'not_submitted' || st.status === 'returned';
  const submitForm = canSubmit ? `
    <div style="border-top:1px solid var(--border);padding-top:16px">
      <h4 style="font-size:14px;font-weight:700;margin-bottom:12px"><i class="bi bi-upload me-2" style="color:var(--accent)"></i>Submit Your Work</h4>
      <div id="submitAlert_${t.id}" style="display:none;margin-bottom:10px"></div>
      <div class="form-group">
        <label class="form-label">Your Response</label>
        <textarea id="subText_${t.id}" class="form-control-custom" rows="4" placeholder="Write your answer or add notes about your submission..." style="resize:vertical"></textarea>
      </div>
      <div class="form-group" style="margin-bottom:16px">
        <label class="form-label">Attach File (optional)</label>
        <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:14px;text-align:center;cursor:pointer" onclick="document.getElementById('subFile_${t.id}').click()">
          <i class="bi bi-cloud-upload" style="font-size:20px;color:var(--text-muted)"></i>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Click to attach your work (PDF, Word, image…)</div>
          <div id="subFileLabel_${t.id}" style="font-size:12px;font-weight:600;color:var(--accent);margin-top:4px"></div>
          <input type="file" id="subFile_${t.id}" style="display:none" onchange="document.getElementById('subFileLabel_${t.id}').textContent=this.files[0]?.name||''">
        </div>
      </div>
      <button class="btn-primary-custom w-100 justify-content-center" onclick="submitTask(${t.id})">
        <i class="bi bi-send"></i> Submit Assignment
      </button>
    </div>` : (st?.status === 'graded' ? '' : `<div style="border-top:1px solid var(--border);padding-top:16px"><p style="font-size:13px;color:var(--text-muted);text-align:center"><i class="bi bi-check-circle-fill me-1" style="color:var(--success)"></i>Submitted and awaiting review</p></div>`);

  body.innerHTML = `
    <div style="margin-bottom:16px">
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px">
        ${t.due_date ? `<span style="font-size:12px;color:var(--text-muted);background:var(--bg);padding:4px 10px;border-radius:99px;border:1px solid var(--border)"><i class="bi bi-calendar me-1"></i>Due: ${new Date(t.due_date).toLocaleString()}</span>` : ''}
        ${t.points ? `<span style="font-size:12px;color:var(--text-muted);background:var(--bg);padding:4px 10px;border-radius:99px;border:1px solid var(--border)"><i class="bi bi-star me-1"></i>${t.points} Points</span>` : ''}
      </div>
      ${t.description ? `<p style="font-size:14px;color:var(--text-muted);line-height:1.7;white-space:pre-wrap">${escapeHtml(t.description)}</p>` : ''}
      ${attachHtml ? `<div style="margin-top:12px"><div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:6px">Resources</div><div style="display:flex;flex-wrap:wrap;gap:6px">${attachHtml}</div></div>` : ''}
    </div>
    ${gradedHtml}
    ${submissionHtml}
    ${submitForm}`;
}

async function submitTask(taskId) {
  const textEl = document.getElementById(`subText_${taskId}`);
  const fileEl = document.getElementById(`subFile_${taskId}`);
  const alertEl = document.getElementById(`submitAlert_${taskId}`);
  const text = textEl?.value.trim();
  const file = fileEl?.files[0];
  if (!text && !file) {
    alertEl.style.display='flex'; alertEl.className='alert-custom alert-error';
    alertEl.innerHTML='<i class="bi bi-x-circle me-2"></i>Please add a response or attach a file.'; return;
  }
  const btn = document.querySelector(`button[onclick="submitTask(${taskId})"]`);
  setButtonLoading(btn, true, 'Submitting...');
  const formData = new FormData();
  formData.append('task_id', taskId);
  if (text) formData.append('submission_text', text);
  if (file) formData.append('submission_file', file);
  const res = await fetch('../php/tasks.php?action=submit_task', { method:'POST', body: formData });
  const data = await res.json();
  setButtonLoading(btn, false);
  if (data.success) {
    Toast.success(data.message);
    Modal.close('taskModal');
    loadTasks(); loadStats();
  } else {
    alertEl.style.display='flex'; alertEl.className='alert-custom alert-error';
    alertEl.innerHTML=`<i class="bi bi-x-circle me-2"></i>${data.message}`;
  }
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    renderTasks(filterTasks(allTasks));
  });
});

function escapeHtml(t) { const d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }
function formatBytes(b) { if(!b)return''; if(b<1024)return b+'B'; if(b<1048576)return(b/1024).toFixed(1)+'KB'; return(b/1048576).toFixed(1)+'MB'; }

loadStats(); loadTasks();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
