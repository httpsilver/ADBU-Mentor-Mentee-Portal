<?php
$user = getCurrentUser();
if (!$user) { logout(); }
$avatar = getAvatarUrl($user['profile_picture'], $user['full_name']);
$role   = $user['role'];

$navItems = [];
if ($role === 'admin') {
    $navItems = [
        ['icon'=>'bi-speedometer2',    'label'=>'Dashboard',        'href'=>'admin-dashboard.php',  'key'=>'dashboard',   'badge'=>''],
        ['icon'=>'bi-people',          'label'=>'Manage Users',     'href'=>'manage-users.php',     'key'=>'users',       'badge'=>''],
        ['icon'=>'bi-diagram-3',       'label'=>'Assign Mentors',   'href'=>'assign-mentors.php',   'key'=>'assign',      'badge'=>''],
        ['icon'=>'bi-list-check',      'label'=>'View Assignments', 'href'=>'view-assignments.php', 'key'=>'assignments', 'badge'=>''],
        ['icon'=>'bi-gear',            'label'=>'Settings',         'href'=>'settings.php',         'key'=>'settings',    'badge'=>''],
        ['icon'=>'bi-info-circle',     'label'=>'About',            'href'=>'about.php',            'key'=>'about',       'badge'=>''],
    ];
} elseif ($role === 'mentor') {
    $navItems = [
        ['icon'=>'bi-speedometer2',    'label'=>'Dashboard',  'href'=>'mentor-dashboard.php', 'key'=>'dashboard', 'badge'=>''],
        ['icon'=>'bi-people',          'label'=>'My Mentees', 'href'=>'my-mentees.php',       'key'=>'mentees',   'badge'=>''],
        ['icon'=>'bi-clipboard-check', 'label'=>'Tasks',      'href'=>'mentor-tasks.php',     'key'=>'tasks',     'badge'=>'tasks'],
        ['icon'=>'bi-chat-dots',       'label'=>'Messages',   'href'=>'chat.php',             'key'=>'chat',      'badge'=>'messages'],
        ['icon'=>'bi-person-circle',   'label'=>'Profile',    'href'=>'profile.php',          'key'=>'profile',   'badge'=>''],
        ['icon'=>'bi-info-circle',     'label'=>'About',      'href'=>'about.php',            'key'=>'about',     'badge'=>''],
    ];
} else {
    $navItems = [
        ['icon'=>'bi-speedometer2',    'label'=>'Dashboard', 'href'=>'mentee-dashboard.php', 'key'=>'dashboard', 'badge'=>''],
        ['icon'=>'bi-person-badge',    'label'=>'My Mentor', 'href'=>'my-mentor.php',        'key'=>'mentor',    'badge'=>''],
        ['icon'=>'bi-clipboard-check', 'label'=>'Tasks',     'href'=>'mentee-tasks.php',     'key'=>'tasks',     'badge'=>'tasks'],
        ['icon'=>'bi-chat-dots',       'label'=>'Messages',  'href'=>'chat.php',             'key'=>'chat',      'badge'=>'messages'],
        ['icon'=>'bi-person-circle',   'label'=>'Profile',   'href'=>'profile.php',          'key'=>'profile',   'badge'=>''],
        ['icon'=>'bi-info-circle',     'label'=>'About',     'href'=>'about.php',            'key'=>'about',     'badge'=>''],
    ];
}

$roleBadge = ['admin'=>'badge-red','mentor'=>'badge-green','mentee'=>'badge-indigo'][$role] ?? 'badge-gray';
?>
<style>
  /*  Nav dot badge  */
  .nav-dot {
    display: none;
    width: 8px; height: 8px;
    background: var(--danger);
    border-radius: 50%;
    margin-left: auto;
    flex-shrink: 0;
    animation: pulse-dot 1.8s infinite;
  }
  .nav-dot.show { display: block; }
  @keyframes pulse-dot {
    0%,100% { transform: scale(1); opacity:1; }
    50%      { transform: scale(1.35); opacity:0.75; }
  }

  /*  Notification bell button  */
  .notif-btn {
    position: relative;
    width: 38px; height: 38px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--text);
    font-size: 17px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: var(--transition);
    flex-shrink: 0;
  }
  .notif-btn:hover { background: var(--accent-light); color: var(--accent); border-color: var(--accent); }
  .notif-btn .notif-count {
    position: absolute;
    top: -5px; right: -5px;
    min-width: 18px; height: 18px;
    background: var(--danger);
    color: white;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 700;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid var(--card);
    line-height: 1;
  }
  .notif-btn .notif-count.show { display: flex; }

  /* Notification dropdown panel  */
  .notif-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 360px;
    max-width: calc(100vw - 24px);
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    z-index: 500;
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: fadeInDown 0.2s ease;
  }
  .notif-dropdown.show { display: flex; }
  .notif-header {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    font-weight: 700; font-size: 14px;
  }
  .notif-list { max-height: 380px; overflow-y: auto; }
  .notif-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
    transition: var(--transition);
    cursor: pointer;
  }
  .notif-item:hover { background: var(--bg); }
  .notif-item:last-child { border-bottom: none; }
  .notif-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    object-fit: cover; flex-shrink: 0;
  }
  .notif-icon-wrap {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
  }
  .notif-text { flex: 1; min-width: 0; }
  .notif-title { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
  .notif-body  { font-size: 12px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .notif-time  { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
  .notif-footer { padding: 10px 16px; text-align: center; border-top: 1px solid var(--border); }
  .notif-empty  { padding: 32px 20px; text-align: center; color: var(--text-muted); font-size: 13px; }
  .notif-empty i { font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.4; }
</style>

<!-- Sidebar backdrop (mobile) -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar" id="mainSidebar">
  <div class="sidebar-brand">
    <img src="../assets/logo.png" alt="ADBU" class="sidebar-brand-img">
    <div class="sidebar-brand-name">ADBU <span>MentorConnect</span></div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-title sidebar-label">Navigation</div>
    <?php foreach ($navItems as $item): ?>
    <a href="<?= $item['href'] ?>" class="nav-item <?= ($currentPage === $item['key']) ? 'active' : '' ?>"
       <?= $item['badge'] ? 'data-nav-badge="'.$item['badge'].'"' : '' ?>>
      <i class="bi <?= $item['icon'] ?>"></i>
      <span class="sidebar-label"><?= $item['label'] ?></span>
      <?php if ($item['badge']): ?>
        <span class="nav-dot" id="dot_<?= $item['badge'] ?>"></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="../php/logout.php" class="nav-item" style="color:var(--danger)">
      <i class="bi bi-box-arrow-left"></i>
      <span class="sidebar-label">Logout</span>
    </a>
  </div>
</aside>

<!-- Main Wrapper -->
<div class="main-wrapper" id="mainWrapper">
  <!-- Top Navbar -->
  <nav class="navbar-top">
    <button class="navbar-toggle-btn" id="sidebarToggle" title="Toggle sidebar">
      <i class="bi bi-list"></i>
    </button>
    <div class="navbar-title"><?= htmlspecialchars($pageTitle) ?></div>

    <div class="navbar-actions">
      <!-- Theme toggle -->
      <button class="theme-icon-btn btn-icon" title="Toggle theme">
        <i class="bi bi-moon-fill"></i>
      </button>

      <!-- Notification bell -->
      <div style="position:relative">
        <button class="notif-btn" id="notifBtn" title="Notifications">
          <i class="bi bi-bell"></i>
          <span class="notif-count" id="notifCount">0</span>
        </button>
        <!-- Notification dropdown -->
        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-header">
            <span><i class="bi bi-bell me-2" style="color:var(--accent)"></i>Notifications</span>
            <span id="notifTotal" style="font-size:12px;font-weight:400;color:var(--text-muted)"></span>
          </div>
          <div class="notif-list" id="notifList">
            <div class="notif-empty"><i class="bi bi-bell-slash"></i>Loading…</div>
          </div>
          <div class="notif-footer" style="display:flex;justify-content:space-between;align-items:center">
            <a href="<?= $role === 'mentor' ? 'chat.php' : 'chat.php' ?>" style="font-size:13px;color:var(--accent);font-weight:600">
              View messages →
            </a>
            <button id="markAllReadBtn" style="font-size:12px;color:var(--text-muted);background:none;border:none;cursor:pointer;font-family:Inter,sans-serif;padding:0">
              Mark all read
            </button>
          </div>
        </div>
      </div>

      <!-- Profile dropdown -->
      <div class="profile-dropdown">
        <button class="profile-btn" id="profileDropBtn">
          <img src="<?= $avatar ?>" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=4F46E5&color=fff'">
          <span class="name"><?= htmlspecialchars($user['full_name']) ?></span>
          <i class="bi bi-chevron-down" style="font-size:11px;color:var(--text-muted)"></i>
        </button>
        <div class="dropdown-menu-custom" id="profileDropMenu">
          <div style="padding:12px 16px;border-bottom:1px solid var(--border)">
            <div style="font-weight:600;font-size:13px;color:var(--text)"><?= htmlspecialchars($user['full_name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($user['email']) ?></div>
            <span class="badge-custom <?= $roleBadge ?>" style="margin-top:4px"><?= ucfirst($role) ?></span>
          </div>
          <a href="profile.php" class="dropdown-item-custom"><i class="bi bi-person"></i> My Profile</a>
          <?php if ($role === 'admin'): ?>
          <a href="settings.php" class="dropdown-item-custom"><i class="bi bi-gear"></i> Settings</a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <a href="../php/logout.php" class="dropdown-item-custom danger"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Page content injected here -->
  <div class="page-content">
<!-- ↑↑ page content starts here — footer closes it ↑↑ -->

<script>
//  Notification system
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const notifCount    = document.getElementById('notifCount');
const notifList     = document.getElementById('notifList');
const notifTotal    = document.getElementById('notifTotal');

let notifOpen   = false;
let notifPoll   = null;
let lastCounts  = { messages: 0, tasks: 0, total: 0 };

function formatNotifTime(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr), n = new Date();
  const diff = n - d;
  if (diff < 60000)    return 'Just now';
  if (diff < 3600000)  return Math.floor(diff / 60000) + 'm ago';
  if (diff < 86400000) return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  return d.toLocaleDateString([], {month:'short', day:'numeric'});
}

function updateBadges(counts) {
  lastCounts = counts || {messages:0, tasks:0, total:0};
  const total = lastCounts.total || 0;

  // Bell count
  if (total > 0) {
    notifCount.textContent = total > 99 ? '99+' : total;
    notifCount.classList.add('show');
  } else {
    notifCount.classList.remove('show');
  }

  // Sidebar red dots
  const msgDot  = document.getElementById('dot_messages');
  const taskDot = document.getElementById('dot_tasks');
  if (msgDot)  msgDot.classList.toggle('show',  (lastCounts.messages || 0) > 0);
  if (taskDot) taskDot.classList.toggle('show', (lastCounts.tasks    || 0) > 0);
}

async function loadNotifications(silent = false) {
  try {
    const res  = await fetch('../php/notifications.php?action=get_all');
    const data = await res.json();
    if (!data.success) return;

    updateBadges(data.counts);

    
    if (!notifOpen && silent) return;

    const notifs = data.notifications || [];
    const total  = (data.counts || {}).total || 0;
    notifTotal.textContent = total > 0 ? total + ' unread' : 'All caught up ✓';

    if (!notifs.length) {
      notifList.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i>No new notifications</div>';
      return;
    }

    notifList.innerHTML = notifs.map(n => `
      <a class="notif-item" href="${n.link || '#'}"
         data-ref-type="${n.ref_type || ''}"
         data-ref-id="${n.ref_id || ''}"
         onclick="handleNotifClick(event, this)">
        <div class="notif-icon-wrap" style="background:${n.bg};color:${n.color}">
          <i class="bi ${n.icon}"></i>
        </div>
        <div class="notif-text">
          <div class="notif-title">${n.title}</div>
          <div class="notif-body">${n.body || ''}</div>
          <div class="notif-time">${formatNotifTime(n.time)}</div>
        </div>
      </a>`).join('');
  } catch(e) { console.error('Notification load error:', e); }
}


async function handleNotifClick(e, el) {
  e.preventDefault();
  const refType = el.dataset.refType;
  const refId   = el.dataset.refId;
  const href    = el.getAttribute('href');

  if (refType && refId) {
    fetch('../php/notifications.php?action=mark_task_seen', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ref_type: refType, ref_id: parseInt(refId)})
    });
  }

  notifDropdown.classList.remove('show');
  notifOpen = false;


  setTimeout(() => loadNotifications(true), 400);

  if (href && href !== '#') window.location.href = href;
}

// Toggle dropdown
notifBtn.addEventListener('click', async (e) => {
  e.stopPropagation();
  notifOpen = !notifOpen;
  notifDropdown.classList.toggle('show', notifOpen);

  if (notifOpen) {
    await loadNotifications(false);
  }
});

document.addEventListener('click', async (e) => {
  if (e.target.id === 'markAllReadBtn') {
    e.preventDefault();
    await fetch('../php/notifications.php?action=mark_all_seen', {method:'POST'});
    await loadNotifications(false);
  }
});


document.addEventListener('click', (e) => {
  if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
    notifDropdown.classList.remove('show');
    notifOpen = false;
  }
});

if (window.location.pathname.includes('chat.php')) {
  
  const urlParams = new URLSearchParams(window.location.search);
  const convId = urlParams.get('conv');
  if (convId) {
    fetch('../php/notifications.php?action=mark_messages_read', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({conversation_id: parseInt(convId)})
    });
  }
}


if (window.location.pathname.includes('tasks.php')) {
  fetch('../php/notifications.php?action=mark_all_seen', {method:'POST'});
}


loadNotifications(true);
notifPoll = setInterval(() => loadNotifications(true), 30000);
</script>
