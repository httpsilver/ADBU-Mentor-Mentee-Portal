<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$action = $_GET['action'] ?? '';

function cleanEmail(string $v): string { return strtolower(trim($v)); }

switch ($action) {

    // Request a reset link 
    case 'request':
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $email   = cleanEmail($body['email'] ?? '');
        $captcha = $body['h-captcha-response'] ?? $body['captcha'] ?? '';

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
            jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.']);

        if (!SMTP_CONFIGURED)
            jsonResponse(['success' => false,
                'message' => 'SMTP is not configured. Open php/config.php and fill in SMTP_USER, SMTP_PASS, and MAIL_FROM with your Gmail address and App Password.']);

        if (!verifyCaptcha($captcha))
            jsonResponse(['success' => false, 'message' => 'CAPTCHA verification failed. Please try again.']);

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE LOWER(email)=? AND is_active=1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare("DELETE FROM password_resets WHERE email=?")->execute([$email]);

            $token = bin2hex(random_bytes(32));
            $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)")
                ->execute([$email, hash('sha256', $token)]);

            $resetUrl = APP_URL . '/pages/reset-password.html?token=' . $token . '&email=' . rawurlencode($email);
            $html     = buildResetEmailHtml($user['full_name'], $resetUrl);

            $result = sendMailDebug($user['email'], $user['full_name'], 'Reset Your ADBU MentorConnect Password', $html);
            logActivity($user['id'], 'Password Reset Requested', $result['success'] ? 'Email sent' : $result['error']);

            if (!$result['success']) {
                // Delete token 
                $pdo->prepare("DELETE FROM password_resets WHERE email=?")->execute([$email]);
                jsonResponse(['success' => false,
                    'message' => 'Could not send email: ' . $result['error']]);
            }
        }

 
        jsonResponse(['success' => true, 'message' => 'Reset link sent! Check your inbox (and spam folder).']);

    // Validate token (called on reset-password page load) 
    case 'validate_token':
        $token = trim($_GET['token'] ?? '');
        $email = cleanEmail($_GET['email'] ?? '');

        if (!$token || !$email)
            jsonResponse(['success' => false, 'message' => 'Invalid or missing reset link.']);

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email=? AND token=? AND expires_at>NOW()");
        $stmt->execute([$email, hash('sha256', $token)]);

        if (!$stmt->fetch())
            jsonResponse(['success' => false, 'message' => 'This reset link is invalid or has expired.']);

        jsonResponse(['success' => true]);

    //  Save the new password 
    case 'reset':
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $token   = trim($body['token'] ?? '');
        $email   = cleanEmail($body['email'] ?? '');
        $pw      = $body['password'] ?? '';
        $confirm = $body['confirm_password'] ?? '';

        if (!$token || !$email) jsonResponse(['success' => false, 'message' => 'Invalid reset link.']);
        if (!$pw)               jsonResponse(['success' => false, 'message' => 'Password is required.']);
        if ($pw !== $confirm)   jsonResponse(['success' => false, 'message' => 'Passwords do not match.']);

        $errs = validatePassword($pw);
        if ($errs) jsonResponse(['success' => false, 'message' => implode(' ', $errs)]);

        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id FROM password_resets WHERE email=? AND token=? AND expires_at>NOW()");
        $stmt->execute([$email, hash('sha256', $token)]);
        if (!$stmt->fetch())
            jsonResponse(['success' => false, 'message' => 'This link is invalid or has already been used.']);

        $pdo->prepare("UPDATE users SET password=? WHERE LOWER(email)=?")
            ->execute([password_hash($pw, PASSWORD_BCRYPT), $email]);
        $pdo->prepare("DELETE FROM password_resets WHERE email=?")->execute([$email]);

        $uid = $pdo->prepare("SELECT id FROM users WHERE LOWER(email)=?");
        $uid->execute([$email]);
        if ($userId = $uid->fetchColumn())
            logActivity($userId, 'Password Reset', 'Password changed via reset link');

        jsonResponse(['success' => true, 'message' => 'Password reset! Redirecting…', 'redirect' => '../index.html']);

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}
?>
