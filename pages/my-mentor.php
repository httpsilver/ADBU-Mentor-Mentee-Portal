<?php
require_once '../php/auth.php';
requireLogin();
if ($_SESSION['user_role'] !== 'mentee') { header('Location: mentor-dashboard.php'); exit; }
$pageTitle   = 'My Mentor';
$currentPage = 'mentor';
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

    <div class="page-header"><h1>My Mentor</h1><p>Your assigned mentor's profile and contact</p></div>

    <div id="mentorContent">
      <div class="empty-state"><div class="loading-spinner" style="border-color:var(--border);border-top-color:var(--accent);width:28px;height:28px;margin:0 auto"></div><p>Loading...</p></div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
async function load() {
  const res = await API.get('../php/messages.php?action=get_conversations');
  const el = document.getElementById('mentorContent');
  if (res.success && res.conversations.length) {
    const m = res.conversations[0];
    el.innerHTML = `
      <div class="row g-4">
        <div class="col-12 col-md-4">
          <div class="card-custom text-center" style="padding:32px">
            <div style="position:relative;display:inline-block;margin-bottom:16px">
              <img src="${m.avatar}" class="avatar" style="width:100px;height:100px;border:3px solid var(--accent)" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(m.partner_name)}&background=4F46E5&color=fff'">
              <span class="online-dot ${m.is_online ? '' : 'offline-dot'}" style="position:absolute;bottom:6px;right:6px;width:14px;height:14px;border:2px solid var(--card)"></span>
            </div>
            <h2 style="font-size:18px;font-weight:800;margin-bottom:4px">${m.partner_name}</h2>
            <span class="badge-custom badge-green" style="margin-bottom:16px">Your Mentor</span>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:20px">
              <i class="bi bi-circle-fill me-1" style="font-size:8px;color:${m.is_online ? 'var(--success)' : 'var(--text-muted)'}"></i>
              ${m.is_online ? 'Currently Online' : 'Offline'}
            </div>
            <a href="chat.php?conv=${m.conversation_id}" class="btn-primary-custom" style="width:100%;justify-content:center">
              <i class="bi bi-chat-dots"></i> Send Message
            </a>
          </div>
        </div>
        <div class="col-12 col-md-8">
          <div class="card-custom">
            <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Contact Information</h3>
            <div style="display:grid;gap:12px">
              <div style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--bg);border-radius:var(--radius-sm)">
                <div style="width:36px;height:36px;background:var(--accent-light);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--accent)">
                  <i class="bi bi-person"></i>
                </div>
                <div>
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em">Full Name</div>
                  <div style="font-weight:600;font-size:14px">${m.partner_name}</div>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--bg);border-radius:var(--radius-sm)">
                <div style="width:36px;height:36px;background:var(--accent-light);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--accent)">
                  <i class="bi bi-mortarboard"></i>
                </div>
                <div>
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em">Role</div>
                  <div style="font-weight:600;font-size:14px">Mentor / Faculty</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  } else {
    el.innerHTML = `<div class="card-custom"><div class="empty-state" style="padding:60px"><i class="bi bi-person-x" style="font-size:48px"></i><h3 style="font-size:16px;font-weight:700;color:var(--text)">No Mentor Assigned</h3><p>An administrator will assign a mentor to you soon. Please check back later.</p></div></div>`;
  }
}
load();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
