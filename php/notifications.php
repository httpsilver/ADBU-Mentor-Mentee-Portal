<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? 'get_all';
$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['user_role'];

// ── Helper: check if a ref has been seen 
function isSeen($pdo, $userId, $refType, $refId) {
    $s = $pdo->prepare("SELECT 1 FROM notifications_seen WHERE user_id=? AND ref_type=? AND ref_id=?");
    $s->execute([$userId, $refType, $refId]);
    return (bool)$s->fetchColumn();
}

// ── Helper: mark a ref as seen
function markSeen($pdo, $userId, $refType, $refId) {
    $s = $pdo->prepare("INSERT IGNORE INTO notifications_seen (user_id, ref_type, ref_id) VALUES (?,?,?)");
    $s->execute([$userId, $refType, $refId]);
}

switch ($action) {

    // ── GET ALL notifications + counts 
    case 'get_all':
        $notifs       = [];
        $msgCount     = 0;
        $taskCount    = 0;

        // ── Unread chat messages 
        if ($role === 'mentor') {
            $stmt = $pdo->prepare("
                SELECT m.id, m.message_text, m.attachment_name, m.message_type, m.sent_at,
                       u.full_name AS sender_name, u.profile_picture,
                       c.id AS conversation_id
                FROM messages m
                JOIN conversations c ON c.id = m.conversation_id
                JOIN assignments a   ON a.id = c.assignment_id
                JOIN users u         ON u.id = m.sender_id
                WHERE a.mentor_id = ? AND m.sender_id != ? AND m.is_read = 0
                ORDER BY m.sent_at DESC LIMIT 20");
        } else {
            $stmt = $pdo->prepare("
                SELECT m.id, m.message_text, m.attachment_name, m.message_type, m.sent_at,
                       u.full_name AS sender_name, u.profile_picture,
                       c.id AS conversation_id
                FROM messages m
                JOIN conversations c ON c.id = m.conversation_id
                JOIN assignments a   ON a.id = c.assignment_id
                JOIN users u         ON u.id = m.sender_id
                WHERE a.mentee_id = ? AND m.sender_id != ? AND m.is_read = 0
                ORDER BY m.sent_at DESC LIMIT 20");
        }
        $stmt->execute([$userId, $userId]);
        $unreadMsgs = $stmt->fetchAll();
        $msgCount   = count($unreadMsgs);
        foreach ($unreadMsgs as $row) {
            $preview = $row['message_type'] === 'file'
                ? '📎 ' . ($row['attachment_name'] ?? 'File attachment')
                : (mb_strlen($row['message_text']) > 70
                    ? mb_substr($row['message_text'], 0, 70) . '…'
                    : $row['message_text']);
            $notifs[] = [
                'id'     => 'msg_' . $row['id'],
                'type'   => 'message',
                'icon'   => 'bi-chat-dots-fill',
                'color'  => '#4F46E5',
                'bg'     => '#EEF2FF',
                'title'  => 'New message from ' . htmlspecialchars($row['sender_name']),
                'body'   => htmlspecialchars($preview),
                'time'   => $row['sent_at'],
                'link'   => 'chat.php?conv=' . $row['conversation_id'],
                'avatar' => getAvatarUrl($row['profile_picture'], $row['sender_name']),
            ];
        }

        // ── Task notifications 
        if ($role === 'mentor') {
            // Unreviewed submissions
            $stmt = $pdo->prepare("
                SELECT ts.id, ts.submitted_at, ts.task_id,
                       t.title AS task_title,
                       u.full_name AS mentee_name, u.profile_picture
                FROM task_submissions ts
                JOIN tasks t ON t.id = ts.task_id
                JOIN users u ON u.id = ts.mentee_id
                WHERE t.mentor_id = ? AND ts.status = 'submitted'
                ORDER BY ts.submitted_at DESC LIMIT 20");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll() as $row) {
                // Only show if not yet seen
                if (!isSeen($pdo, $userId, 'submission', $row['id'])) {
                    $taskCount++;
                    $notifs[] = [
                        'id'     => 'sub_' . $row['id'],
                        'ref_type'=> 'submission',
                        'ref_id' => $row['id'],
                        'type'   => 'submission',
                        'icon'   => 'bi-send-check-fill',
                        'color'  => '#059669',
                        'bg'     => '#D1FAE5',
                        'title'  => htmlspecialchars($row['mentee_name']) . ' submitted a task',
                        'body'   => htmlspecialchars($row['task_title']),
                        'time'   => $row['submitted_at'],
                        'link'   => 'mentor-tasks.php',
                        'avatar' => getAvatarUrl($row['profile_picture'], $row['mentee_name']),
                    ];
                }
            }
        } else {
            // New unsubmitted tasks (not yet seen)
            $stmt = $pdo->prepare("
                SELECT t.id, t.title, t.created_at, t.due_date, t.points,
                       u.full_name AS mentor_name, u.profile_picture
                FROM tasks t
                JOIN users u ON u.id = t.mentor_id
                LEFT JOIN task_submissions ts ON ts.task_id = t.id AND ts.mentee_id = ?
                WHERE t.mentee_id = ? AND t.status = 'published'
                  AND (ts.id IS NULL OR ts.status = 'not_submitted')
                ORDER BY t.created_at DESC LIMIT 20");
            $stmt->execute([$userId, $userId]);
            foreach ($stmt->fetchAll() as $row) {
                if (!isSeen($pdo, $userId, 'task', $row['id'])) {
                    $taskCount++;
                    $notifs[] = [
                        'id'      => 'task_' . $row['id'],
                        'ref_type'=> 'task',
                        'ref_id'  => $row['id'],
                        'type'    => 'task',
                        'icon'    => 'bi-clipboard-check-fill',
                        'color'   => '#D97706',
                        'bg'      => '#FEF3C7',
                        'title'   => 'New task: ' . htmlspecialchars($row['title']),
                        'body'    => 'From ' . htmlspecialchars($row['mentor_name'])
                                     . ($row['due_date'] ? ' · Due ' . date('M j', strtotime($row['due_date'])) : ''),
                        'time'    => $row['created_at'],
                        'link'    => 'mentee-tasks.php',
                        'avatar'  => getAvatarUrl($row['profile_picture'], $row['mentor_name']),
                    ];
                }
            }

            // Newly graded tasks (not yet seen)
            $stmt = $pdo->prepare("
                SELECT ts.id, ts.grade, ts.graded_at,
                       t.title AS task_title, t.points,
                       u.full_name AS mentor_name, u.profile_picture
                FROM task_submissions ts
                JOIN tasks t ON t.id = ts.task_id
                JOIN users u ON u.id = t.mentor_id
                WHERE ts.mentee_id = ? AND ts.status = 'graded'
                ORDER BY ts.graded_at DESC LIMIT 20");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll() as $row) {
                if (!isSeen($pdo, $userId, 'grade', $row['id'])) {
                    $taskCount++;
                    $score = $row['grade'] !== null
                        ? $row['grade'] . ($row['points'] ? '/' . $row['points'] : '') . ' pts'
                        : 'Graded';
                    $notifs[] = [
                        'id'      => 'grade_' . $row['id'],
                        'ref_type'=> 'grade',
                        'ref_id'  => $row['id'],
                        'type'    => 'grade',
                        'icon'    => 'bi-star-fill',
                        'color'   => '#7C3AED',
                        'bg'      => '#EDE9FE',
                        'title'   => 'Task graded: ' . htmlspecialchars($row['task_title']),
                        'body'    => 'Score: ' . $score . ' · From ' . htmlspecialchars($row['mentor_name']),
                        'time'    => $row['graded_at'],
                        'link'    => 'mentee-tasks.php',
                        'avatar'  => getAvatarUrl($row['profile_picture'], $row['mentor_name']),
                    ];
                }
            }
        }

        usort($notifs, fn($a, $b) => strtotime($b['time'] ?? 0) - strtotime($a['time'] ?? 0));

        $total = $msgCount + $taskCount;
        jsonResponse([
            'success'       => true,
            'notifications' => array_slice($notifs, 0, 30),
            'counts'        => [
                'messages' => $msgCount,
                'tasks'    => $taskCount,
                'total'    => $total,
            ],
        ]);
        break;

    // ── Mark all chat messages read in a conversation ─────────
    case 'mark_messages_read':
        $data   = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $convId = (int)($data['conversation_id'] ?? $_GET['conversation_id'] ?? 0);
        if ($convId) {
            
            $check = $pdo->prepare("SELECT c.id FROM conversations c JOIN assignments a ON a.id=c.assignment_id WHERE c.id=? AND (a.mentor_id=? OR a.mentee_id=?)");
            $check->execute([$convId, $userId, $userId]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?")
                    ->execute([$convId, $userId]);
            }
        }
        jsonResponse(['success' => true]);

    // ── Mark task/grade/submission notification as seen ───────
    case 'mark_task_seen':
        $data    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $refType = $data['ref_type'] ?? '';
        $refId   = (int)($data['ref_id'] ?? 0);
        if (in_array($refType, ['task','grade','submission']) && $refId > 0) {
            markSeen($pdo, $userId, $refType, $refId);
        }
        jsonResponse(['success' => true]);

    // ── Mark ALL current notifications as seen ────────────────
    case 'mark_all_seen':
        // Mark all unread messages read
        if ($role === 'mentor') {
            $pdo->prepare("UPDATE messages m JOIN conversations c ON c.id=m.conversation_id JOIN assignments a ON a.id=c.assignment_id SET m.is_read=1 WHERE a.mentor_id=? AND m.sender_id!=?")->execute([$userId, $userId]);
        } else {
            $pdo->prepare("UPDATE messages m JOIN conversations c ON c.id=m.conversation_id JOIN assignments a ON a.id=c.assignment_id SET m.is_read=1 WHERE a.mentee_id=? AND m.sender_id!=?")->execute([$userId, $userId]);
        }

        // Mark all task notifications as seen
        if ($role === 'mentor') {
            $stmt = $pdo->prepare("SELECT ts.id FROM task_submissions ts JOIN tasks t ON t.id=ts.task_id WHERE t.mentor_id=? AND ts.status='submitted'");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll() as $r) markSeen($pdo, $userId, 'submission', $r['id']);
        } else {
            // New tasks
            $stmt = $pdo->prepare("SELECT t.id FROM tasks t LEFT JOIN task_submissions ts ON ts.task_id=t.id AND ts.mentee_id=? WHERE t.mentee_id=? AND t.status='published' AND (ts.id IS NULL OR ts.status='not_submitted')");
            $stmt->execute([$userId, $userId]);
            foreach ($stmt->fetchAll() as $r) markSeen($pdo, $userId, 'task', $r['id']);
            // Graded tasks
            $stmt = $pdo->prepare("SELECT ts.id FROM task_submissions ts JOIN tasks t ON t.id=ts.task_id WHERE ts.mentee_id=? AND ts.status='graded'");
            $stmt->execute([$userId]);
            foreach ($stmt->fetchAll() as $r) markSeen($pdo, $userId, 'grade', $r['id']);
        }
        jsonResponse(['success' => true]);

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
?>
