<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data            = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$fullName        = sanitize($data['full_name'] ?? '');
$email           = sanitize($data['email'] ?? '');
$password        = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';
$role            = $data['role'] ?? '';
$captcha         = $data['h-captcha-response'] ?? $data['captcha'] ?? '';

$errors = [];

// Basic field validation
if (empty($fullName))        $errors[] = 'Full name is required.';
if (strlen($fullName) < 2)   $errors[] = 'Full name must be at least 2 characters.';
if (empty($email))           $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
if (empty($password))        $errors[] = 'Password is required.';
if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
if (!in_array($role, ['mentor', 'mentee'])) $errors[] = 'Invalid role selected.';

// Password strength
$pwErrors = validatePassword($password);
$errors   = array_merge($errors, $pwErrors);

if (!empty($errors)) {
    jsonResponse(['success' => false, 'message' => implode(' ', $errors)]);
}

// CAPTCHA
if (!verifyCaptcha($captcha)) {
    jsonResponse(['success' => false, 'message' => 'CAPTCHA verification failed. Please try again.']);
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'This email is already registered.']);
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,?)")
    ->execute([$fullName, $email, $hashedPassword, $role]);
$userId = $pdo->lastInsertId();

logActivity($userId, 'Registration', "New $role account created");
jsonResponse(['success' => true, 'message' => 'Account created! Please sign in.', 'redirect' => '../index.html']);
?>
