<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/auth.php';

requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$role   = $_SESSION['user_role'];

$attachDir = __DIR__ . '/../assets/uploads/attachments/';
$maxSize   = 20 * 1024 * 1024; 

function verifyConvAccess($pdo, $convId, $userId) {
    $stmt = $pdo->prepare("SELECT c.id FROM conversations c JOIN assignments a ON a.id=c.assignment_id WHERE c.id=? AND (a.mentor_id=? OR a.mentee_id=?)");
    $stmt->execute([$convId, $userId, $userId]);
    return $stmt->fetch();
}

switch ($action) {

    case 'get_conversations':
        if ($role === 'mentor') {
            $stmt = $pdo->prepare("
                SELECT a.id as assignment_id, c.id as conversation_id,
                       u.id as partner_id, u.full_name as partner_name, u.profile_picture, u.is_online,
                       (SELECT COALESCE(message_text, attachment_name) FROM messages WHERE conversation_id=c.id ORDER BY sent_at DESC LIMIT 1) as last_message,
                       (SELECT message_type FROM messages WHERE conversation_id=c.id ORDER BY sent_at DESC LIMIT 1) as last_message_type,
                       (SELECT sent_at FROM messages WHERE conversation_id=c.id ORDER BY sent_at DESC LIMIT 1) as last_message_time,
                       (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id AND sender_id!=? AND is_read=0) as unread_count
                FROM assignments a JOIN conversations c ON c.assignment_id=a.id
                JOIN users u ON u.id=a.mentee_id
                WHERE a.mentor_id=? AND a.is_active=1 ORDER BY last_message_time DESC");
            $stmt->execute([$userId, $userId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT a.id as assignment_id, c.id as conversation_id,
                       u.id as partner_id, u.full_name as partner_name, u.profile_picture, u.is_online,
                       (SELECT COALESCE(message_text, attachment_name) FROM messages WHERE conversation_id=c.id ORDER BY sent_at DESC LIMIT 1) as last_message,
                       (SELECT message_type FROM messages WHERE conversation_id=c.id ORDER BY sent_at DESC LIMIT 1) as last_message_type,
                       (SELECT sent_at FROM messages WHERE conversation_id=c.id ORDER BY sent_at DESC LIMIT 1) as last_message_time,
                       (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id AND sender_id!=? AND is_read=0) as unread_count
                FROM assignments a JOIN conversations c ON c.assignment_id=a.id
                JOIN users u ON u.id=a.mentor_id
                WHERE a.mentee_id=? AND a.is_active=1 ORDER BY last_message_time DESC");
            $stmt->execute([$userId, $userId]);
        }
        $convs = $stmt->fetchAll();
        foreach ($convs as &$c) {
            $c['avatar'] = getAvatarUrl($c['profile_picture'], $c['partner_name']);
            if ($c['last_message_type'] === 'file') $c['last_message'] = '📎 ' . ($c['last_message'] ?? 'File');
        }
        jsonResponse(['success' => true, 'conversations' => $convs]);

    case 'get_messages':
        $convId = (int)($_GET['conversation_id'] ?? 0);
        if (!$convId) jsonResponse(['success' => false, 'message' => 'Invalid conversation']);
        if (!verifyConvAccess($pdo, $convId, $userId)) jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $pdo->prepare("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?")->execute([$convId, $userId]);
        $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name, u.profile_picture FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.sent_at ASC");
        $stmt->execute([$convId]);
        $messages = $stmt->fetchAll();
        foreach ($messages as &$msg) {
            $msg['avatar']  = getAvatarUrl($msg['profile_picture'], $msg['sender_name']);
            $msg['is_mine'] = ($msg['sender_id'] == $userId);
        }
        jsonResponse(['success' => true, 'messages' => $messages, 'user_id' => $userId]);

    case 'send_message':
        $data   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $convId = (int)($data['conversation_id'] ?? 0);
        $text   = trim($data['message'] ?? '');
        if (!$convId || empty($text)) jsonResponse(['success' => false, 'message' => 'Invalid data']);
        if (!verifyConvAccess($pdo, $convId, $userId)) jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message_text, message_type) VALUES (?,?,?,'text')");
        $stmt->execute([$convId, $userId, $text]);
        $msgId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.id=?");
        $stmt->execute([$msgId]);
        $message = $stmt->fetch();
        $message['is_mine'] = true;
        $message['avatar']  = getAvatarUrl(null, $message['sender_name']);
        jsonResponse(['success' => true, 'message' => $message]);

    case 'send_file':
        $convId = (int)($_POST['conversation_id'] ?? 0);
        if (!$convId) jsonResponse(['success' => false, 'message' => 'Invalid conversation']);
        if (!verifyConvAccess($pdo, $convId, $userId)) jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        if (empty($_FILES['file'])) jsonResponse(['success' => false, 'message' => 'No file received']);
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) jsonResponse(['success' => false, 'message' => 'Upload error']);
        if ($file['size'] > $maxSize) jsonResponse(['success' => false, 'message' => 'File too large (max 20MB)']);
        if (!is_dir($attachDir)) mkdir($attachDir, 0755, true);
        $origName = $file['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $safeName = 'chat_' . $convId . '_' . $userId . '_' . time() . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $attachDir . $safeName);
        $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message_text, attachment_path, attachment_name, attachment_size, attachment_type, message_type) VALUES (?,?,NULL,?,?,?,?,'file')");
        $stmt->execute([$convId, $userId, $safeName, $origName, $file['size'], $file['type']]);
        $msgId = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT m.*, u.full_name as sender_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.id=?");
        $stmt->execute([$msgId]);
        $message = $stmt->fetch();
        $message['is_mine'] = true;
        $message['avatar']  = getAvatarUrl(null, $message['sender_name']);
        jsonResponse(['success' => true, 'message' => $message]);

    case 'download':
        $msgId = (int)($_GET['msg_id'] ?? 0);
        $stmt  = $pdo->prepare("SELECT m.* FROM messages m JOIN conversations c ON c.id=m.conversation_id JOIN assignments a ON a.id=c.assignment_id WHERE m.id=? AND (a.mentor_id=? OR a.mentee_id=?)");
        $stmt->execute([$msgId, $userId, $userId]);
        $msg = $stmt->fetch();
        if (!$msg || !$msg['attachment_path']) { http_response_code(404); die('File not found'); }
        $path = $attachDir . $msg['attachment_path'];
        if (!file_exists($path)) { http_response_code(404); die('File not found on server'); }
        header('Content-Type: ' . ($msg['attachment_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($msg['attachment_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
?>
