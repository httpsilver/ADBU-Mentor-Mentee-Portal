<?php
require_once '../php/auth.php';
requireAdmin();
$pageTitle   = 'View Assignments';
$currentPage = 'assignments';
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

    <div class="page-header-row mb-4">
      <div class="page-header" style="margin:0">
        <h1>View Assignments</h1>
        <p>All mentor-mentee pairs in the system</p>
      </div>
      <div style="display:flex;gap:10px">
        <input type="text" id="searchAssign" class="form-control-custom" placeholder="🔍 Search..." style="width:220px">
        <a href="assign-mentors.php" class="btn-primary-custom"><i class="bi bi-plus"></i> New Assignment</a>
      </div>
    </div>

    <!-- Stats row -->
    <div class="row g-3 mb-4" id="assignStats">
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon indigo"><i class="bi bi-diagram-3"></i></div><div><div class="stat-value" id="totalAssign">—</div><div class="stat-label">Total</div></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div><div class="stat-value" id="activeAssign">—</div><div class="stat-label">Active</div></div></div>
      </div>
    </div>

    <div class="card-custom">
      <div style="overflow-x:auto">
        <table class="table-custom">
          <thead>
            <tr><th>#</th><th>Mentor</th><th>Mentee</th><th>Assigned By</th><th>Date</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody id="assignBody">
            <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text-muted)">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
let allAssignments = [];

async function loadAssignments() {
  const res = await API.get('../php/admin.php?action=get_assignments');
  if (res.success) {
    allAssignments = res.assignments;
    document.getElementById('totalAssign').textContent = allAssignments.length;
    document.getElementById('activeAssign').textContent = allAssignments.filter(a => a.is_active).length;
    renderTable(allAssignments);
  }
}

function renderTable(data) {
  const tbody = document.getElementById('assignBody');
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="bi bi-diagram-3"></i><p>No assignments found.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = data.map((a, i) => `
    <tr>
      <td style="color:var(--text-muted);font-size:13px">${i + 1}</td>
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
      <td>
        ${a.is_active ? `<button class="btn-danger-custom" style="padding:6px 12px;font-size:12px" onclick="removeAssignment(${a.id})">
          <i class="bi bi-x-circle"></i> Remove
        </button>` : '<span style="font-size:12px;color:var(--text-muted)">Removed</span>'}
      </td>
    </tr>
  `).join('');
}

async function removeAssignment(id) {
  confirmAction('Remove this assignment?', async () => {
    const res = await API.post('../php/admin.php?action=delete_assignment', { id });
    if (res.success) { Toast.success(res.message); loadAssignments(); }
    else Toast.error(res.message);
  });
}

document.getElementById('searchAssign').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  renderTable(allAssignments.filter(a =>
    a.mentor_name.toLowerCase().includes(q) || a.mentee_name.toLowerCase().includes(q)
  ));
});

loadAssignments();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
