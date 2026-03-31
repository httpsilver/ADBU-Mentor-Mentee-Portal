<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Login / session helpers 
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin($redirect = '../index.html') {
    if (!isLoggedIn()) { header("Location: $redirect"); exit; }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout();
    }
    $_SESSION['last_activity'] = time();
}

function requireRole($role, $redirect = '../pages/dashboard.php') {
    requireLogin();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        header("Location: $redirect"); exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: ../pages/dashboard.php"); exit;
    }
}

function logout() {
    $pdo = getDB();
    if (isset($_SESSION['user_id'])) {
        $pdo->prepare("UPDATE users SET is_online=0, last_seen=NOW() WHERE id=?")->execute([$_SESSION['user_id']]);
    }
    session_destroy();
    header("Location: ../index.html"); exit;
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, email, role, profile_picture, is_online FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// ── Password validation 
function validatePassword($password) {
    $errors = [];
    if (strlen($password) < 8)                          $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[0-9]/', $password))              $errors[] = 'Password must contain at least one number.';
    if (!preg_match('/[^a-zA-Z0-9]/', $password))       $errors[] = 'Password must contain at least one special character (!@#$%^ etc).';
    return $errors;
}

// ── hCaptcha verification 
function verifyCaptcha($token) {
    if (!CAPTCHA_ENABLED) return true;
    if (empty(trim($token))) return false;

    $ch = curl_init('https://api.hcaptcha.com/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => HCAPTCHA_SECRET,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false, 
    ]);
    $result = curl_exec($ch);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($err || !$result) {
        
        error_log("hCaptcha cURL error: $err");
        return false;
    }

    $data = json_decode($result, true);
    return !empty($data['success']);
}

// ── Email helper (SMTP via Mailer class) 
function sendMail(string $to, string $toName, string $subject, string $htmlBody): bool {
    require_once __DIR__ . '/PHPMailer/Mailer.php';
    $m = new \ADMentorConnect\Mailer();
    $m->configure(SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION, SMTP_USER, SMTP_PASS, MAIL_FROM, MAIL_FROM_NAME);
    $ok = $m->send($to, $toName, $subject, $htmlBody);
    if (!$ok) {
        error_log('[MentorPortal sendMail] ' . $m->getLastError());
    }
    return $ok;
}


function sendMailDebug(string $to, string $toName, string $subject, string $htmlBody): array {
    require_once __DIR__ . '/PHPMailer/Mailer.php';
    $m = new \ADMentorConnect\Mailer();
    $m->configure(SMTP_HOST, SMTP_PORT, SMTP_ENCRYPTION, SMTP_USER, SMTP_PASS, MAIL_FROM, MAIL_FROM_NAME);
    $ok = $m->send($to, $toName, $subject, $htmlBody);
    return ['success' => $ok, 'error' => $m->getLastError(), 'debug_log' => $m->getDebugLog()];
}

function buildResetEmailHtml($name, $resetUrl) {
    return '<!DOCTYPE html><html><body style="margin:0;padding:0;font-family:Inter,Arial,sans-serif;background:#F9FAFB">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 20px">
<table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;border:1px solid #E5E7EB;overflow:hidden">
  <tr><td style="background:linear-gradient(135deg,#4F46E5,#7C3AED);padding:32px;text-align:center">
    <div style="font-size:28px;margin-bottom:8px">🎓</div>
    <h1 style="color:white;margin:0;font-size:22px;font-weight:800">ADBU MentorConnect</h1>
  </td></tr>
  <tr><td style="padding:36px 40px">
    <h2 style="color:#111827;font-size:20px;font-weight:700;margin:0 0 12px">Password Reset Request</h2>
    <p style="color:#6B7280;font-size:15px;line-height:1.6;margin:0 0 24px">Hi ' . htmlspecialchars($name) . ', we received a request to reset your ADBU MentorConnect password. Click the button below to create a new password. This link expires in <strong>1 hour</strong>.</p>
    <div style="text-align:center;margin:28px 0">
      <a href="' . $resetUrl . '" style="display:inline-block;background:#4F46E5;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px">Reset My Password</a>
    </div>
    <p style="color:#9CA3AF;font-size:13px;line-height:1.6">If you didn\'t request a password reset, you can safely ignore this email. The link will expire automatically.</p>
    <hr style="border:none;border-top:1px solid #E5E7EB;margin:24px 0">
    <p style="color:#9CA3AF;font-size:12px;text-align:center;margin:0">If the button doesn\'t work, copy this link:<br><span style="color:#4F46E5;word-break:break-all">' . $resetUrl . '</span></p>
  </td></tr>
</table></td></tr></table></body></html>';
}

// ── Notifications 

function getNotificationCounts($userId, $role) {
    $pdo = getDB();
    $counts = ['messages' => 0, 'tasks' => 0, 'total' => 0];
    try {
      
        if ($role === 'mentor') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages m JOIN conversations c ON c.id=m.conversation_id JOIN assignments a ON a.id=c.assignment_id WHERE a.mentor_id=? AND m.sender_id!=? AND m.is_read=0");
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages m JOIN conversations c ON c.id=m.conversation_id JOIN assignments a ON a.id=c.assignment_id WHERE a.mentee_id=? AND m.sender_id!=? AND m.is_read=0");
        }
        $stmt->execute([$userId, $userId]);
        $counts['messages'] = (int)$stmt->fetchColumn();

        if ($role === 'mentor') {
           
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM task_submissions ts JOIN tasks t ON t.id=ts.task_id WHERE t.mentor_id=? AND ts.status='submitted'");
            $stmt->execute([$userId]);
        } else {
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t LEFT JOIN task_submissions ts ON ts.task_id=t.id AND ts.mentee_id=? WHERE t.mentee_id=? AND t.status='published' AND (ts.status IS NULL OR ts.status='not_submitted' OR ts.status='graded')");
            $stmt->execute([$userId, $userId]);
        }
        $counts['tasks'] = (int)$stmt->fetchColumn();
        $counts['total'] = $counts['messages'] + $counts['tasks'];
    } catch (Exception $e) {}
    return $counts;
}

// ── Activity log 
function logActivity($userId, $action, $details = null) {
    try {
        $pdo = getDB();
        $pdo->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?,?,?)")->execute([$userId, $action, $details]);
    } catch (Exception $e) {}
}

// ── Avatar URL
function getAvatarUrl($picture, $name) {
    if ($picture && file_exists(UPLOAD_PATH . $picture)) {
        return UPLOAD_URL . $picture;
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=4F46E5&color=fff&size=128";
}

// ── JSON response 
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ── Sanitize 
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>
