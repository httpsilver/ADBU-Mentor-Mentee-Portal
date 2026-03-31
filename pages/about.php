<?php
require_once '../php/auth.php';
requireLogin();
$pageTitle   = 'About';
$currentPage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About – ADBU MentorConnect</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<div class="app-layout">
<?php include 'sidebar_nav.php'; ?>

  <div class="page-header">
    <h1>About ADBU MentorConnect</h1>
    <p>Don Bosco University's mentorship platform</p>
  </div>

  <!-- Hero card -->
  <div style="background:linear-gradient(135deg,#1e3a5f,#4F46E5);border-radius:var(--radius-lg);padding:2.5rem 2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:2rem;flex-wrap:wrap">
    <img src="../assets/logo.png" alt="ADBU Logo"
         style="width:7rem;height:7rem;object-fit:contain;flex-shrink:0">
    <div>
      <h2 style="color:white;font-size:1.75rem;font-weight:800;margin-bottom:.5rem">
        ADBU MentorConnect
      </h2>
      <p style="color:rgba(255,255,255,.8);font-size:1rem;max-width:40rem;margin:0">
        A dedicated mentorship management platform for Assam Don Bosco University,
        bridging faculty mentors and students to foster academic excellence and personal growth.
      </p>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(16rem,1fr));gap:1.25rem;margin-bottom:1.5rem">

    <!-- About the university -->
    <div class="card-custom">
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
        <div style="width:2.5rem;height:2.5rem;background:#EEF2FF;border-radius:.625rem;display:flex;align-items:center;justify-content:center;color:#4F46E5;font-size:1.25rem">
          <i class="bi bi-bank"></i>
        </div>
        <h3 style="font-size:1rem;font-weight:700;margin:0">Don Bosco University</h3>
      </div>
      <p style="font-size:.875rem;line-height:1.7;margin:0">
        Assam Don Bosco University (ADBU) is a private university located in Guwahati, Assam.
        Founded on the Salesian tradition of "Carpe Diem – Life in its Fullness," the university
        is committed to holistic education, technical excellence, and human development.
      </p>
    </div>

    <!-- About the platform -->
    <div class="card-custom">
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
        <div style="width:2.5rem;height:2.5rem;background:#D1FAE5;border-radius:.625rem;display:flex;align-items:center;justify-content:center;color:#059669;font-size:1.25rem">
          <i class="bi bi-people-fill"></i>
        </div>
        <h3 style="font-size:1rem;font-weight:700;margin:0">The Platform</h3>
      </div>
      <p style="font-size:.875rem;line-height:1.7;margin:0">
        ADBU MentorConnect enables structured, one-on-one mentorship between faculty and students.
        Mentors can track progress, assign tasks, and communicate directly with their mentees —
        all in one place, accessible on any device.
      </p>
    </div>

    <!-- Mission -->
    <div class="card-custom">
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
        <div style="width:2.5rem;height:2.5rem;background:#FEF3C7;border-radius:.625rem;display:flex;align-items:center;justify-content:center;color:#D97706;font-size:1.25rem">
          <i class="bi bi-stars"></i>
        </div>
        <h3 style="font-size:1rem;font-weight:700;margin:0">Our Mission</h3>
      </div>
      <p style="font-size:.875rem;line-height:1.7;margin:0">
        To empower every ADBU student with a dedicated mentor who provides guidance,
        monitors academic progress, and supports personal growth — in the spirit of
        <em>"Life in its Fullness."</em>
      </p>
    </div>

  </div>

  <!-- Features -->
  <div class="card-custom" style="margin-bottom:1.5rem">
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem">
      <i class="bi bi-grid-fill me-2" style="color:var(--accent)"></i>Platform Features
    </h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(13rem,1fr));gap:.875rem">
      <?php
      $features = [
        ['bi-chat-dots-fill',     '#4F46E5','#EEF2FF', 'Real-time Messaging',    'Direct chat between mentors and mentees with file sharing.'],
        ['bi-clipboard-check-fill','#059669','#D1FAE5', 'Task Management',        'Assign, submit, and grade tasks like Google Classroom.'],
        ['bi-bell-fill',          '#D97706','#FEF3C7', 'Smart Notifications',    'Instant alerts for messages, new tasks, and grades.'],
        ['bi-shield-lock-fill',   '#7C3AED','#EDE9FE', 'Secure Access',          'Captcha-protected login, password reset, and role-based access.'],
        ['bi-phone-fill',         '#0284C7','#E0F2FE', 'Mobile Friendly',        'Fully responsive design — works on phones, tablets, and desktops.'],
        ['bi-moon-stars-fill',    '#374151','#F3F4F6', 'Dark Mode',              'Switch between light and dark themes for comfortable reading.'],
      ];
      foreach ($features as [$icon, $color, $bg, $title, $desc]): ?>
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem">
        <div style="width:2rem;height:2rem;background:<?= $bg ?>;border-radius:.375rem;display:flex;align-items:center;justify-content:center;color:<?= $color ?>;font-size:1rem;margin-bottom:.625rem">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div style="font-size:.875rem;font-weight:600;color:var(--text);margin-bottom:.25rem"><?= $title ?></div>
        <div style="font-size:.75rem;color:var(--text-muted);line-height:1.5"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Contact / University info -->
  <div class="card-custom">
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem">
      <i class="bi bi-geo-alt-fill me-2" style="color:var(--accent)"></i>Contact &amp; Location
    </h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem;font-size:.875rem">
      <div style="display:flex;gap:.75rem;align-items:flex-start">
        <i class="bi bi-building" style="color:var(--accent);font-size:1.125rem;margin-top:.1rem;flex-shrink:0"></i>
        <div><strong style="color:var(--text)">Assam Don Bosco University</strong><br>
          <span style="color:var(--text-muted)">Azara, Guwahati, Assam 781017, India</span></div>
      </div>
      <div style="display:flex;gap:.75rem;align-items:flex-start">
        <i class="bi bi-globe" style="color:var(--accent);font-size:1.125rem;margin-top:.1rem;flex-shrink:0"></i>
        <div><strong style="color:var(--text)">Website</strong><br>
          <a href="https://www.dbuniversity.ac.in" target="_blank" style="color:var(--accent)">www.dbuniversity.ac.in</a></div>
      </div>
      <div style="display:flex;gap:.75rem;align-items:flex-start">
        <i class="bi bi-envelope-fill" style="color:var(--accent);font-size:1.125rem;margin-top:.1rem;flex-shrink:0"></i>
        <div><strong style="color:var(--text)">Platform Support</strong><br>
          <a href="mailto:mentorportalemailserver@gmail.com" style="color:var(--accent)">mentorportalemailserver@gmail.com</a></div>
      </div>
    </div>
  </div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
