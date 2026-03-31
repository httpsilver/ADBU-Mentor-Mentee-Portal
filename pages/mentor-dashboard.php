<?php
require_once '../php/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'mentor') { header('Location: mentee-dashboard.php'); exit; }
$pageTitle   = 'Mentor Dashboard';
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
      <h2>👋 Hello, <?= htmlspecialchars(implode(' ', array_slice(explode(' ', $user['full_name']), 0, 2))) ?>!</h2>
      <p>You have <strong id="menteeCount" style="color:rgba(255,255,255,0.9)">—</strong> mentees assigned to you. Keep up the great work!</p>
    </div>

    <!-- Assigned Mentees -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h3 style="font-size:16px;font-weight:700">My Mentees</h3>
      <div style="display:flex;gap:8px">
        <a href="mentor-tasks.php" class="btn-secondary-custom" style="padding:7px 14px;font-size:13px">
          <i class="bi bi-clipboard-check"></i> Tasks
        </a>
        <a href="chat.php" class="btn-secondary-custom" style="padding:7px 14px;font-size:13px">
          <i class="bi bi-chat-dots"></i> Open Messages
        </a>
      </div>
    </div>

    <div class="row g-3 mb-4" id="menteesGrid">
      <div class="col-12 text-center" style="padding:32px;color:var(--text-muted)">
        <div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto 8px"></div>
        Loading your mentees...
      </div>
    </div>

    <!-- Recent Conversations -->
    <div class="card-custom">
      <h3 style="font-size:16px;font-weight:700;margin-bottom:16px">Recent Conversations</h3>
      <div id="recentConvos">
        <div class="empty-state"><i class="bi bi-chat"></i><p>Loading conversations...</p></div>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
async function loadDashboard() {
  const res = await API.get('../php/messages.php?action=get_conversations');
  const grid = document.getElementById('menteesGrid');
  const convos = document.getElementById('recentConvos');

  if (res.success && res.conversations.length) {
    document.getElementById('menteeCount').textContent = res.conversations.length;
    grid.innerHTML = res.conversations.map(c => `
      <div class="col-12 col-sm-6 col-xl-4">
        <div class="card-custom animate-in" style="text-align:center">
          <div style="position:relative;display:inline-block;margin-bottom:12px">
            <img src="${c.avatar}" class="avatar avatar-lg" style="border:3px solid var(--accent)" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.partner_name)}&background=4F46E5&color=fff'">
            <span class="online-dot ${c.is_online ? '' : 'offline-dot'}" style="position:absolute;bottom:4px;right:4px;width:12px;height:12px;border:2px solid var(--card)"></span>
          </div>
          <div style="font-weight:700;font-size:15px;margin-bottom:4px">${c.partner_name}</div>
          <span class="badge-custom badge-indigo" style="margin-bottom:12px">Mentee</span>
          <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            ${c.last_message ? `"${c.last_message.substring(0, 50)}..."` : 'No messages yet'}
          </p>
          <a href="chat.php?conv=${c.conversation_id}" class="btn-primary-custom" style="width:100%;justify-content:center">
            <i class="bi bi-chat-dots"></i> Start Chat
            ${c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : ''}
          </a>
        </div>
      </div>
    `).join('');

    convos.innerHTML = res.conversations.slice(0, 5).map(c => `
      <a href="chat.php?conv=${c.conversation_id}" style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:var(--radius-sm);transition:var(--transition);text-decoration:none" class="conv-item">
        <img src="${c.avatar}" class="avatar avatar-sm" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.partner_name)}&background=4F46E5&color=fff'">
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:14px;color:var(--text)">${c.partner_name}</div>
          <div style="font-size:12px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${c.last_message || 'No messages yet'}</div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
          <span style="font-size:11px;color:var(--text-muted)">${formatTime(c.last_message_time)}</span>
          ${c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : ''}
        </div>
      </a>
    `).join('');
  } else {
    document.getElementById('menteeCount').textContent = '0';
    grid.innerHTML = `<div class="col-12"><div class="empty-state"><i class="bi bi-people"></i><p>No mentees assigned yet. Contact your administrator.</p></div></div>`;
    convos.innerHTML = `<div class="empty-state"><i class="bi bi-chat"></i><p>No conversations yet.</p></div>`;
  }
}

document.querySelectorAll('.conv-item').forEach(item => {
  item.addEventListener('mouseenter', () => item.style.background = 'var(--accent-light)');
  item.addEventListener('mouseleave', () => item.style.background = '');
});

loadDashboard();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
