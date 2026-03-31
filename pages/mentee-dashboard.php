<?php
require_once '../php/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'mentee') { header('Location: mentor-dashboard.php'); exit; }
$pageTitle   = 'Mentee Dashboard';
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
      <h2>👋 Welcome, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>!</h2>
      <p>Connect with your mentor and make the most of your mentoring journey.</p>
    </div>

    <div class="row g-4">
      <!-- Mentor Card -->
      <div class="col-12 col-lg-5">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px">My Mentor</h3>
        <div id="mentorCard">
          <div class="card-custom text-center" style="padding:32px">
            <div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto 12px"></div>
            <p>Loading mentor info...</p>
          </div>
        </div>
      </div>

      <!-- Recent conversations -->
      <div class="col-12 col-lg-7">
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px">Recent Conversations</h3>
        <div id="recentConvos" class="card-custom">
          <div class="empty-state"><i class="bi bi-chat"></i><p>Loading...</p></div>
        </div>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
async function loadDashboard() {
  const res = await API.get('../php/messages.php?action=get_conversations');
  const mentorCard = document.getElementById('mentorCard');
  const convos     = document.getElementById('recentConvos');

  if (res.success && res.conversations.length) {
    const mentor = res.conversations[0];
    mentorCard.innerHTML = `
      <div class="card-custom animate-in" style="text-align:center;padding:28px">
        <div style="position:relative;display:inline-block;margin-bottom:16px">
          <img src="${mentor.avatar}" class="avatar avatar-xl" style="border:3px solid var(--accent)" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(mentor.partner_name)}&background=4F46E5&color=fff'">
          <span class="online-dot ${mentor.is_online ? '' : 'offline-dot'}" style="position:absolute;bottom:6px;right:6px;width:14px;height:14px;border:2px solid var(--card)"></span>
        </div>
        <div style="font-weight:800;font-size:17px;margin-bottom:4px">${mentor.partner_name}</div>
        <span class="badge-custom badge-green" style="margin-bottom:6px">Your Mentor</span>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:20px">
          <i class="bi bi-circle-fill me-1" style="font-size:8px;color:${mentor.is_online ? 'var(--success)' : 'var(--text-muted)'}"></i>
          ${mentor.is_online ? 'Online now' : 'Offline'}
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;width:100%">
          <a href="chat.php?conv=${mentor.conversation_id}" class="btn-primary-custom" style="width:100%;justify-content:center;font-size:15px">
            <i class="bi bi-chat-dots"></i> Message Mentor
            ${mentor.unread_count > 0 ? `<span class="unread-badge">${mentor.unread_count}</span>` : ''}
          </a>
          <a href="mentee-tasks.php" class="btn-secondary-custom" style="width:100%;justify-content:center;font-size:14px">
            <i class="bi bi-clipboard-check"></i> View My Tasks
          </a>
          <a href="my-mentor.php" class="btn-ghost-custom" style="width:100%;justify-content:center;font-size:14px">
            <i class="bi bi-person-circle"></i> View Profile
          </a>
        </div>
      </div>`;

    convos.innerHTML = res.conversations.map(c => `
      <a href="chat.php?conv=${c.conversation_id}" style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:var(--radius-sm);transition:var(--transition);text-decoration:none;border-bottom:1px solid var(--border)">
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
    mentorCard.innerHTML = `<div class="card-custom"><div class="empty-state"><i class="bi bi-person-x"></i><p>No mentor assigned yet. Please wait for an administrator to assign you a mentor.</p></div></div>`;
    convos.innerHTML = `<div class="empty-state"><i class="bi bi-chat"></i><p>No conversations yet.</p></div>`;
  }
}

loadDashboard();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
