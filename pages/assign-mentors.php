<?php
require_once '../php/auth.php';
requireAdmin();
$pageTitle   = 'Assign Mentors';
$currentPage = 'assign';
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
</head>
<body>
<div class="app-layout">
  <?php include 'sidebar_nav.php'; ?>

    <div class="page-header">
      <h1>Assign Mentors</h1>
      <p>Select a mentor and a mentee to create a new assignment</p>
    </div>

    <div id="assignAlert" style="display:none;margin-bottom:16px"></div>

    <!-- Assignment Form -->
    <div class="card-custom mb-4">
      <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Create New Assignment</h3>
      <div class="row g-4">
        <!-- Mentor List -->
        <div class="col-12 col-md-5">
          <div class="assign-panel">
            <div class="assign-panel-header">
              <i class="bi bi-person-badge me-2" style="color:var(--success)"></i>Select Mentor
            </div>
            <div style="padding:10px;border-bottom:1px solid var(--border)">
              <input type="text" class="form-control-custom" id="mentorSearch" placeholder="🔍 Search mentors...">
            </div>
            <div class="assign-panel-body" id="mentorList">
              <div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>
            </div>
          </div>
        </div>

        <!-- Arrow -->
        <div class="col-12 col-md-2 assign-connect">
          <div style="text-align:center">
            <div style="font-size:28px;color:var(--accent)" id="arrowIcon">⇄</div>
            <button class="btn-primary-custom" id="assignBtn" style="margin-top:12px;width:100%" disabled>
              <i class="bi bi-link-45deg"></i> Assign
            </button>
          </div>
        </div>

        <!-- Mentee List -->
        <div class="col-12 col-md-5">
          <div class="assign-panel">
            <div class="assign-panel-header">
              <i class="bi bi-person me-2" style="color:var(--accent)"></i>Select Mentee
            </div>
            <div style="padding:10px;border-bottom:1px solid var(--border)">
              <input type="text" class="form-control-custom" id="menteeSearch" placeholder="🔍 Search mentees...">
            </div>
            <div class="assign-panel-body" id="menteeList">
              <div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Existing Assignments -->
    <div class="card-custom">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 style="font-size:16px;font-weight:700;margin:0">Existing Assignments</h3>
        <button class="btn-ghost-custom" onclick="loadAssignments()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
      </div>
      <div style="overflow-x:auto">
        <table class="table-custom">
          <thead>
            <tr><th>Mentor</th><th>Mentee</th><th>Assigned By</th><th>Date</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody id="assignmentsBody">
            <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted)">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
let mentors = [], mentees = [];
let selectedMentor = null, selectedMentee = null;

async function loadUsers() {
  const [mr, me] = await Promise.all([
    API.get('../php/admin.php?action=get_users&role=mentor'),
    API.get('../php/admin.php?action=get_users&role=mentee')
  ]);
  if (mr.success) { mentors = mr.users; renderList('mentorList', mentors, 'mentor'); }
  if (me.success) { mentees = me.users; renderList('menteeList', mentees, 'mentee'); }
}

function renderList(containerId, users, type) {
  const el = document.getElementById(containerId);
  if (!users.length) {
    el.innerHTML = `<div class="empty-state"><i class="bi bi-person-x"></i><p>No ${type}s found.</p></div>`;
    return;
  }
  el.innerHTML = users.map(u => `
    <div class="user-card" id="${type}_${u.id}" onclick="selectUser('${type}', ${u.id}, this)" style="margin-bottom:8px;flex-direction:row;text-align:left;padding:12px 14px">
      <img src="${u.avatar}" class="avatar avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(u.full_name)}&background=4F46E5&color=fff'">
      <div style="flex:1;min-width:0">
        <div class="user-name" style="font-size:13px">${u.full_name}</div>
        <div class="user-email">${u.email}</div>
      </div>
      <span class="badge-custom ${u.is_online ? 'badge-green' : 'badge-gray'}" style="font-size:10px">${u.is_online ? 'Online' : 'Offline'}</span>
    </div>
  `).join('');
}

function selectUser(type, id, el) {
  document.querySelectorAll(`[id^="${type}_"]`).forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  if (type === 'mentor') selectedMentor = id;
  else selectedMentee = id;
  updateAssignBtn();
}

function updateAssignBtn() {
  document.getElementById('assignBtn').disabled = !(selectedMentor && selectedMentee);
}

document.getElementById('assignBtn').addEventListener('click', async () => {
  const btn = document.getElementById('assignBtn');
  const alert = document.getElementById('assignAlert');
  setButtonLoading(btn, true, 'Assigning...');
  const res = await API.post('../php/admin.php?action=create_assignment', { mentor_id: selectedMentor, mentee_id: selectedMentee });
  setButtonLoading(btn, false);
  if (res.success) {
    alert.style.display = 'flex';
    alert.className = 'alert-custom alert-success';
    alert.innerHTML = `<i class="bi bi-check-circle me-2"></i>${res.message}`;
    selectedMentor = selectedMentee = null;
    document.querySelectorAll('.user-card.selected').forEach(c => c.classList.remove('selected'));
    updateAssignBtn();
    loadAssignments();
    setTimeout(() => alert.style.display = 'none', 3000);
  } else {
    alert.style.display = 'flex';
    alert.className = 'alert-custom alert-error';
    alert.innerHTML = `<i class="bi bi-x-circle me-2"></i>${res.message}`;
  }
});

async function loadAssignments() {
  const res = await API.get('../php/admin.php?action=get_assignments');
  const tbody = document.getElementById('assignmentsBody');
  if (res.success && res.assignments.length) {
    tbody.innerHTML = res.assignments.map(a => `
      <tr style="${a.is_active ? '' : 'opacity:0.55'}">
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <img src="${a.mentor_avatar}" class="avatar avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(a.mentor_name)}&background=10B981&color=fff'">
            <div>
              <div style="font-weight:600;font-size:13px">${a.mentor_name}</div>
              <div style="font-size:11px;color:var(--text-muted)">${a.mentor_email}</div>
            </div>
          </div>
        </td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <img src="${a.mentee_avatar}" class="avatar avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(a.mentee_name)}&background=4F46E5&color=fff'">
            <div>
              <div style="font-weight:600;font-size:13px">${a.mentee_name}</div>
              <div style="font-size:11px;color:var(--text-muted)">${a.mentee_email}</div>
            </div>
          </div>
        </td>
        <td style="font-size:13px;color:var(--text-muted)">${a.assigned_by_name}</td>
        <td style="font-size:12px;color:var(--text-muted)">${new Date(a.assigned_at).toLocaleDateString()}</td>
        <td><span class="badge-custom ${a.is_active ? 'badge-green' : 'badge-gray'}">${a.is_active ? 'Active' : 'Inactive'}</span></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
          ${a.is_active
            ? `<button class="btn-danger-custom" style="padding:6px 12px;font-size:12px" onclick="removeAssignment(${a.id})">
                <i class="bi bi-x"></i> Remove
               </button>`
            : `<button class="btn-success-custom" style="padding:6px 12px;font-size:12px" onclick="restoreAssignment(${a.id})">
                <i class="bi bi-arrow-counterclockwise"></i> Restore
               </button>`
          }
        </td>
      </tr>
    `).join('');
  } else {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="bi bi-diagram-3"></i><p>No assignments yet.</p></div></td></tr>`;
  }
}

function removeAssignment(id) {
  confirmAction('Remove this assignment? The conversation history will be preserved.', async () => {
    const res = await API.post('../php/admin.php?action=delete_assignment', { id });
    if (res.success) { Toast.success(res.message); loadAssignments(); }
    else Toast.error(res.message);
  });
}

function restoreAssignment(id) {
  confirmAction('Restore this assignment and make it active again?', async () => {
    const res = await API.post('../php/admin.php?action=restore_assignment', { id });
    if (res.success) { Toast.success(res.message); loadAssignments(); }
    else Toast.error(res.message);
  });
}

// Search filtering
document.getElementById('mentorSearch').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  renderList('mentorList', mentors.filter(u => u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)), 'mentor');
});
document.getElementById('menteeSearch').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  renderList('menteeList', mentees.filter(u => u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)), 'mentee');
});

loadUsers();
loadAssignments();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
