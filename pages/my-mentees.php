<?php
require_once '../php/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'mentor') { header('Location: mentee-dashboard.php'); exit; }
$pageTitle   = 'My Mentees';
$currentPage = 'mentees';
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

    <div class="page-header"><h1>My Mentees</h1><p>Students assigned to you for mentoring</p></div>

    <div class="row g-3" id="menteesGrid">
      <div class="col-12 text-center" style="padding:40px;color:var(--text-muted)">
        <div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:24px;height:24px;margin:0 auto"></div>
      </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
async function load() {
  const res = await API.get('../php/messages.php?action=get_conversations');
  const grid = document.getElementById('menteesGrid');
  if (res.success && res.conversations.length) {
    grid.innerHTML = res.conversations.map(c => `
      <div class="col-12 col-sm-6 col-xl-4">
        <div class="card-custom animate-in" style="text-align:center">
          <div style="position:relative;display:inline-block;margin-bottom:12px">
            <img src="${c.avatar}" class="avatar avatar-lg" style="border:3px solid var(--accent)" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.partner_name)}&background=4F46E5&color=fff'">
            <span class="online-dot ${c.is_online ? '' : 'offline-dot'}" style="position:absolute;bottom:4px;right:4px;width:12px;height:12px;border:2px solid var(--card)"></span>
          </div>
          <div style="font-weight:700;font-size:15px;margin-bottom:4px">${c.partner_name}</div>
          <span class="badge-custom badge-indigo" style="margin-bottom:8px">Mentee</span>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:16px">
            ${c.is_online ? '<i class="bi bi-circle-fill me-1" style="color:var(--success);font-size:8px"></i>Online' : '<i class="bi bi-circle me-1" style="font-size:8px"></i>Offline'}
          </div>
          <div style="display:grid;gap:8px">
            <a href="chat.php?conv=${c.conversation_id}" class="btn-primary-custom" style="justify-content:center">
              <i class="bi bi-chat-dots"></i> Send Message
              ${c.unread_count > 0 ? `<span class="unread-badge">${c.unread_count}</span>` : ''}
            </a>
          </div>
        </div>
      </div>
    `).join('');
  } else {
    grid.innerHTML = `<div class="col-12"><div class="card-custom"><div class="empty-state" style="padding:60px"><i class="bi bi-people" style="font-size:48px"></i><h3 style="font-size:16px;font-weight:700;color:var(--text)">No Mentees Yet</h3><p>You have no mentees assigned. Please contact an administrator.</p></div></div></div>`;
  }
}
load();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
