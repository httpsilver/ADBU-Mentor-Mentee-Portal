<?php
require_once '../php/auth.php';
requireLogin();
$pageTitle   = 'My Profile';
$currentPage = 'profile';
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

    <div class="row g-4">
      <!-- Profile Picture & Info -->
      <div class="col-12 col-md-4">
        <div class="card-custom text-center">
          <div class="profile-picture-upload" id="picUploadZone" onclick="document.getElementById('picInput').click()">
            <img id="profilePic" src="" class="avatar avatar-xl" style="border:3px solid var(--accent)" onerror="this.src='https://ui-avatars.com/api/?name=User&background=4F46E5&color=fff'">
            <div>
              <div style="font-weight:600;font-size:13px;color:var(--text)">Change Photo</div>
              <div style="font-size:12px;color:var(--text-muted)">JPG, PNG or GIF • Max 5MB</div>
            </div>
            <input type="file" id="picInput" accept="image/*" style="display:none">
          </div>
          <div style="margin-top:16px">
            <div style="font-weight:700;font-size:17px" id="displayName">—</div>
            <span class="badge-custom <?= $_SESSION['user_role'] === 'mentor' ? 'badge-green' : ($_SESSION['user_role'] === 'admin' ? 'badge-red' : 'badge-indigo') ?>" style="margin-top:6px">
              <?= ucfirst($_SESSION['user_role']) ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Edit Info -->
      <div class="col-12 col-md-8">
        <div class="card-custom mb-4">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Personal Information</h3>
          <div id="profileAlert" style="display:none;margin-bottom:16px"></div>
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <div class="input-group-custom">
              <i class="bi bi-person input-icon"></i>
              <input type="text" id="fullName" class="form-control-custom" placeholder="Your full name">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address <small style="color:var(--text-muted)">(read-only)</small></label>
            <div class="input-group-custom">
              <i class="bi bi-envelope input-icon"></i>
              <input type="email" id="email" class="form-control-custom" readonly style="cursor:not-allowed">
            </div>
          </div>
          <button class="btn-primary-custom" id="saveProfileBtn">
            <i class="bi bi-check2"></i> Save Changes
          </button>
        </div>

        <!-- Change Password -->
        <div class="card-custom">
          <h3 style="font-size:16px;font-weight:700;margin-bottom:20px">Change Password</h3>
          <div id="pwdAlert" style="display:none;margin-bottom:16px"></div>
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <div class="input-group-custom">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" id="currentPwd" class="form-control-custom" placeholder="Enter current password">
              <i class="bi bi-eye input-icon-right" id="toggleCurrentPwd" style="cursor:pointer"></i>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="input-group-custom">
              <i class="bi bi-lock-fill input-icon"></i>
              <input type="password" id="newPwd" class="form-control-custom" placeholder="Create a strong password">
              <i class="bi bi-eye input-icon-right" id="toggleNewPwd" style="cursor:pointer"></i>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="pwdStrengthFill"></div></div>
            <div id="pwd-req-len"  class="req"><i class="bi bi-circle"></i> At least 8 characters</div>
            <div id="pwd-req-num"  class="req"><i class="bi bi-circle"></i> At least 1 number</div>
            <div id="pwd-req-spec" class="req"><i class="bi bi-circle"></i> At least 1 special character</div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <div class="input-group-custom">
              <i class="bi bi-lock-fill input-icon"></i>
              <input type="password" id="confirmPwd" class="form-control-custom" placeholder="Repeat new password">
              <i class="bi bi-eye input-icon-right" id="toggleConfirmPwd" style="cursor:pointer"></i>
            </div>
          </div>
          <button class="btn-secondary-custom" id="changePwdBtn">
            <i class="bi bi-shield-lock"></i> Update Password
          </button>
        </div>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
async function loadProfile() {
  const res = await API.get('../php/profile.php?action=get_profile');
  if (res.success) {
    const u = res.user;
    document.getElementById('fullName').value = u.full_name;
    document.getElementById('email').value    = u.email;
    document.getElementById('displayName').textContent = u.full_name;
    document.getElementById('profilePic').src = u.avatar;
  }
}

// Save profile
document.getElementById('saveProfileBtn').addEventListener('click', async () => {
  const btn   = document.getElementById('saveProfileBtn');
  const alert = document.getElementById('profileAlert');
  setButtonLoading(btn, true, 'Saving...');
  const res = await API.post('../php/profile.php?action=update_profile', {
    full_name: document.getElementById('fullName').value
  });
  setButtonLoading(btn, false);
  showAlert(alert, res.message, res.success ? 'success' : 'error');
  if (res.success) {
    document.getElementById('displayName').textContent = document.getElementById('fullName').value;
    Toast.success('Profile updated!');
  }
});

//  Password visibility toggles 
[['toggleCurrentPwd','currentPwd'],['toggleNewPwd','newPwd'],['toggleConfirmPwd','confirmPwd']].forEach(([btnId, inputId]) => {
  document.getElementById(btnId).addEventListener('click', function () {
    const inp = document.getElementById(inputId);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    this.className = 'bi ' + (isText ? 'bi-eye' : 'bi-eye-slash') + ' input-icon-right';
    this.style.cursor = 'pointer';
  });
});

//  Password strength meter
const strengthColors = ['','#EF4444','#F59E0B','#3B82F6','#10B981','#059669'];
function setPwdReq(id, met) {
  const el = document.getElementById(id);
  el.className = 'req ' + (met ? 'met' : '');
  el.querySelector('i').className = 'bi ' + (met ? 'bi-check-circle-fill' : 'bi-circle');
}
document.getElementById('newPwd').addEventListener('input', function () {
  const v = this.value;
  const len  = v.length >= 8;
  const num  = /[0-9]/.test(v);
  const spec = /[^a-zA-Z0-9]/.test(v);
  const upper = /[A-Z]/.test(v);
  setPwdReq('pwd-req-len',  len);
  setPwdReq('pwd-req-num',  num);
  setPwdReq('pwd-req-spec', spec);
  const score = [v.length > 0, len, num, spec, upper && len].filter(Boolean).length;
  const fill = document.getElementById('pwdStrengthFill');
  fill.style.width = (score * 20) + '%';
  fill.style.background = strengthColors[score] || '';
});

// Change password
document.getElementById('changePwdBtn').addEventListener('click', async () => {
  const btn   = document.getElementById('changePwdBtn');
  const alert = document.getElementById('pwdAlert');
  const currentPwd = document.getElementById('currentPwd').value;
  const newPwd     = document.getElementById('newPwd').value;
  const confirmPwd = document.getElementById('confirmPwd').value;

  // Client-side validation matching registration constraints
  if (!currentPwd) { showAlert(alert, 'Please enter your current password.', 'error'); return; }
  if (newPwd.length < 8) { showAlert(alert, 'New password must be at least 8 characters.', 'error'); return; }
  if (!/[0-9]/.test(newPwd)) { showAlert(alert, 'New password must contain at least 1 number.', 'error'); return; }
  if (!/[^a-zA-Z0-9]/.test(newPwd)) { showAlert(alert, 'New password must contain at least 1 special character.', 'error'); return; }
  if (newPwd !== confirmPwd) { showAlert(alert, 'Passwords do not match.', 'error'); return; }

  setButtonLoading(btn, true, 'Updating...');
  const res = await API.post('../php/profile.php?action=change_password', {
    current_password: currentPwd,
    new_password:     newPwd,
    confirm_password: confirmPwd,
  });
  setButtonLoading(btn, false);
  showAlert(alert, res.message, res.success ? 'success' : 'error');
  if (res.success) {
    document.getElementById('currentPwd').value = '';
    document.getElementById('newPwd').value = '';
    document.getElementById('confirmPwd').value = '';
    Toast.success('Password changed!');
  }
});

// Picture upload
document.getElementById('picInput').addEventListener('change', async e => {
  const file = e.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('picture', file);
  formData.append('action', 'upload_picture');
  const res = await fetch('../php/profile.php?action=upload_picture', { method: 'POST', body: formData });
  const data = await res.json();
  if (data.success) {
    const reader = new FileReader();
    reader.onload = ev => {
      document.getElementById('profilePic').src = ev.target.result;
    };
    reader.readAsDataURL(file);
    Toast.success('Profile picture updated!');
  } else {
    Toast.error(data.message);
  }
});

// Drag & drop
const zone = document.getElementById('picUploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = 'var(--accent)'; });
zone.addEventListener('dragleave', () => zone.style.borderColor = '');
zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.style.borderColor = '';
  const file = e.dataTransfer.files[0];
  if (file) document.getElementById('picInput').files = e.dataTransfer.files;
});

function showAlert(el, msg, type) {
  el.style.display = 'flex';
  el.className = `alert-custom alert-${type}`;
  el.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'x-circle'} me-2"></i>${msg}`;
  setTimeout(() => el.style.display = 'none', 4000);
}

loadProfile();
</script>
<?php include 'footer.php'; ?>
</body>
</html>
