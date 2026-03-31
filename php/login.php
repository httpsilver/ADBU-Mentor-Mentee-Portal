<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email    = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';
$captcha  = $data['h-captcha-response'] ?? $data['captcha'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email and password are required.']);
}

// CAPTCHA verification
if (!verifyCaptcha($captcha)) {
    jsonResponse(['success' => false, 'message' => 'CAPTCHA verification failed. Please try again.']);
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND is_active=1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
}

$_SESSION['user_id']       = $user['id'];
$_SESSION['user_role']     = $user['role'];
$_SESSION['user_name']     = $user['full_name'];
$_SESSION['last_activity'] = time();

$pdo->prepare("UPDATE users SET is_online=1, last_seen=NOW() WHERE id=?")->execute([$user['id']]);
logActivity($user['id'], 'Login', 'User logged in successfully');

$redirectMap = [
    'admin'  => 'pages/admin-dashboard.php',
    'mentor' => 'pages/mentor-dashboard.php',
    'mentee' => 'pages/mentee-dashboard.php',
];

jsonResponse([
    'success'  => true,
    'message'  => 'Login successful',
    'role'     => $user['role'],
    'redirect' => $redirectMap[$user['role']] ?? 'pages/dashboard.php'
]);
?>
