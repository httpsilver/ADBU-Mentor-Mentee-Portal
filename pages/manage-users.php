<?php
require_once '../php/auth.php';
requireAdmin();
$pageTitle   = 'Manage Users';
$currentPage = 'users';
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

    <div class="page-header-row">
      <div class="page-header">
        <h1>Manage Users</h1>
        <p>View and manage all mentors and mentees</p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <select id="roleFilter" class="form-control-custom" style="width:auto">
          <option value="">All Roles</option>
          <option value="mentor">Mentors</option>
          <option value="mentee">Mentees</option>
        </select>
        <input type="text" id="searchUsers" class="form-control-custom" placeholder="🔍 Search users..." style="width:220px">
      </div>
    </div>

    <div class="card-custom">
      <div style="overflow-x:auto">
        <table class="table-custom" id="usersTable">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="usersBody">
            <tr><td colspan="6" style="text-align:center;padding:32px">
              <div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div>
            </td></tr>
          </tbody>
        </table>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
let allUsers = [];

async function loadUsers(role = '') {
  const res = await API.get(`../php/admin.php?action=get_users${role ? '&role=' + role : ''}`);
  if (res.success) {
    allUsers = res.users;
    renderUsers(allUsers);
  }
}

function renderUsers(users) {
  const tbody = document.getElementById('usersBody');
  if (!users.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><i class="bi bi-people"></i><p>No users found.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = users.map(u => `
    <tr class="animate-in" data-id="${u.id}">
      <td>
        <div style="display:flex;align-items:center;gap:10px">
          <img src="${u.avatar}" class="avatar avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(u.full_name)}&background=4F46E5&color=fff'">
          <div style="font-weight:600;font-size:14px">${u.full_name}</div>
        </div>
      </td>
      <td style="font-size:13px;color:var(--text-muted)">${u.email}</td>
      <td><span class="badge-custom ${u.role === 'mentor' ? 'badge-green' : 'badge-indigo'}">${u.role}</span></td>
      <td>
        <span class="badge-custom ${u.is_active ? 'badge-green' : 'badge-red'}">
          ${u.is_active ? 'Active' : 'Inactive'}
        </span>
      </td>
      <td style="font-size:12px;color:var(--text-muted)">${new Date(u.created_at).toLocaleDateString()}</td>
      <td>
        <div style="display:flex;gap:6px">
          <button class="btn-icon" title="${u.is_active ? 'Deactivate' : 'Activate'}" onclick="toggleUser(${u.id}, this)">
            <i class="bi ${u.is_active ? 'bi-pause-circle' : 'bi-play-circle'}"></i>
          </button>
          <button class="btn-icon" title="Delete" onclick="deleteUser(${u.id}, '${u.full_name}')" style="color:var(--danger)">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function toggleUser(id, btn) {
  const res = await API.post('../php/admin.php?action=toggle_user', { id });
  if (res.success) { Toast.success(res.message); loadUsers(document.getElementById('roleFilter').value); }
  else Toast.error(res.message);
}

function deleteUser(id, name) {
  confirmAction(`Delete user "${name}"? This cannot be undone.`, async () => {
    const res = await API.post('../php/admin.php?action=delete_user', { id });
    if (res.success) { Toast.success(res.message); loadUsers(document.getElementById('roleFilter').value); }
    else Toast.error(res.message);
  });
}

document.getElementById('roleFilter').addEventListener('change', e => loadUsers(e.target.value));
document.getElementById('searchUsers').addEventListener('input', e => {
  const q = e.target.value.toLowerCase();
  renderUsers(allUsers.filter(u => u.full_name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)));
});

loadUsers();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
