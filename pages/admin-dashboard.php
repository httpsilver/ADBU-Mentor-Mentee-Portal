<?php
require_once '../php/auth.php';
requireAdmin();
$pageTitle   = 'Admin Dashboard';
$currentPage = 'dashboard';
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

    <!-- Welcome -->
    <div class="welcome-card">
      <h2>👋 Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</h2>
      <p>Here's what's happening in your mentoring portal today.</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4" id="statsRow">
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon indigo"><i class="bi bi-person-badge"></i></div>
          <div>
            <div class="stat-value" id="statMentors">—</div>
            <div class="stat-label">Total Mentors</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-people"></i></div>
          <div>
            <div class="stat-value" id="statMentees">—</div>
            <div class="stat-label">Total Mentees</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon amber"><i class="bi bi-diagram-3"></i></div>
          <div>
            <div class="stat-value" id="statAssignments">—</div>
            <div class="stat-label">Active Assignments</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
          <div class="stat-icon rose"><i class="bi bi-chat-dots"></i></div>
          <div>
            <div class="stat-value" id="statMessages">—</div>
            <div class="stat-label">Total Messages</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card-custom">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 style="font-size:16px;font-weight:700;margin:0">Recent Activity</h3>
        <a href="manage-users.php" style="font-size:13px;font-weight:600">View all →</a>
      </div>
      <div style="overflow-x:auto">
        <table class="table-custom" id="activityTable">
          <thead>
            <tr>
              <th>User</th>
              <th>Action</th>
              <th>Details</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody id="activityBody">
            <tr><td colspan="4" class="text-center" style="padding:32px;color:var(--text-muted)">
              <div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div>
            </td></tr>
          </tbody>
        </table>
      </div>
    </div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
  async function loadStats() {
    const res = await API.get('../php/admin.php?action=get_stats');
    if (res.success) {
      document.getElementById('statMentors').textContent     = res.stats.total_mentors;
      document.getElementById('statMentees').textContent     = res.stats.total_mentees;
      document.getElementById('statAssignments').textContent = res.stats.active_assignments;
      document.getElementById('statMessages').textContent    = res.stats.total_messages;
    }
  }

  async function loadActivity() {
    const res = await API.get('../php/admin.php?action=get_activity');
    const tbody = document.getElementById('activityBody');
    if (res.success && res.activity.length) {
      tbody.innerHTML = res.activity.map(item => `
        <tr class="animate-in">
          <td>
            <div style="display:flex;align-items:center;gap:10px">
              <img src="${item.avatar}" class="avatar avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(item.full_name)}&background=4F46E5&color=fff'">
              <div>
                <div style="font-weight:600;font-size:13px">${item.full_name}</div>
                <span class="badge-custom ${item.role === 'mentor' ? 'badge-green' : item.role === 'admin' ? 'badge-red' : 'badge-indigo'}">${item.role}</span>
              </div>
            </div>
          </td>
          <td><span style="font-weight:500">${item.action}</span></td>
          <td style="color:var(--text-muted);font-size:13px">${item.details || '—'}</td>
          <td style="font-size:12px;color:var(--text-muted)">${formatTime(item.created_at)}</td>
        </tr>
      `).join('');
    } else {
      tbody.innerHTML = `<tr><td colspan="4"><div class="empty-state"><i class="bi bi-activity"></i><p>No activity yet.</p></div></td></tr>`;
    }
  }

  loadStats();
  loadActivity();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
