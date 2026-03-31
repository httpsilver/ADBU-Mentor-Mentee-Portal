<?php
require_once '../php/auth.php';
requireAdmin();
$pageTitle   = 'Settings';
$currentPage = 'settings';
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

    <div class="page-header"><h1>Settings</h1><p>Portal configuration and preferences</p></div>

    <div class="row g-4">
      <div class="col-12 col-lg-6">
        <div class="card-custom mb-4">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px"><i class="bi bi-palette me-2" style="color:var(--accent)"></i>Appearance</h3>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border)">
            <div>
              <div style="font-weight:600;font-size:14px">Dark Mode</div>
              <div style="font-size:12px;color:var(--text-muted)">Switch between light and dark themes</div>
            </div>
            <button class="theme-icon-btn btn-icon" title="Toggle theme" style="width:42px;height:42px;border-radius:50%;font-size:18px">
              <i class="bi bi-moon-fill"></i>
            </button>
          </div>
        </div>

        <div class="card-custom">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px"><i class="bi bi-shield-lock me-2" style="color:var(--accent)"></i>Security</h3>
          <div style="padding:12px 0;border-bottom:1px solid var(--border)">
            <div style="font-weight:600;font-size:14px;margin-bottom:4px">Session Timeout</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px">Automatically log out after inactivity</div>
            <select class="form-control-custom" style="width:auto">
              <option>1 hour</option>
              <option>2 hours</option>
              <option>4 hours</option>
              <option>8 hours</option>
            </select>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="card-custom">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px"><i class="bi bi-info-circle me-2" style="color:var(--accent)"></i>About</h3>
          <div style="display:grid;gap:12px">
            <?php
            $infos = [
              ['Application', 'Mentor-Mentee Management Portal'],
              ['Version', '1.0.0'],
              ['Framework', 'PHP 8 + Bootstrap 5'],
              ['Database', 'MySQL 8'],
            ];
            foreach ($infos as [$label, $value]):
            ?>
            <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
              <span style="font-size:13px;color:var(--text-muted)"><?= $label ?></span>
              <span style="font-size:13px;font-weight:600"><?= $value ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>
