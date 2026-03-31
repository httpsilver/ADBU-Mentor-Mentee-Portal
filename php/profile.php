<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/auth.php';

requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

switch ($action) {

    case 'get_profile':
        $stmt = $pdo->prepare("SELECT id, full_name, email, role, profile_picture, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $user['avatar'] = getAvatarUrl($user['profile_picture'], $user['full_name']);
        jsonResponse(['success' => true, 'user' => $user]);

    case 'update_profile':
        $data     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $fullName = sanitize($data['full_name'] ?? '');
        if (empty($fullName)) jsonResponse(['success' => false, 'message' => 'Name is required']);

        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->execute([$fullName, $userId]);
        $_SESSION['user_name'] = $fullName;
        logActivity($userId, 'Profile Updated', 'User updated their profile');
        jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);

    case 'change_password':
        $data        = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $current     = $data['current_password'] ?? '';
        $new         = $data['new_password'] ?? '';
        $confirm     = $data['confirm_password'] ?? '';

        if (empty($current) || empty($new)) jsonResponse(['success' => false, 'message' => 'All fields required']);
        if (strlen($new) < 8) jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters']);
        if ($new !== $confirm) jsonResponse(['success' => false, 'message' => 'Passwords do not match']);

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password'])) {
            jsonResponse(['success' => false, 'message' => 'Current password is incorrect']);
        }

        $hashed = password_hash($new, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $userId]);
        logActivity($userId, 'Password Changed', 'User changed their password');
        jsonResponse(['success' => true, 'message' => 'Password changed successfully']);

    case 'upload_picture':
        if (!isset($_FILES['picture'])) jsonResponse(['success' => false, 'message' => 'No file uploaded']);
        $file    = $_FILES['picture'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) jsonResponse(['success' => false, 'message' => 'Invalid file type']);
        if ($file['size'] > 5 * 1024 * 1024) jsonResponse(['success' => false, 'message' => 'File too large (max 5MB)']);

        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

        $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->execute([$filename, $userId]);
        jsonResponse(['success' => true, 'message' => 'Picture updated', 'filename' => $filename]);

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
?>
