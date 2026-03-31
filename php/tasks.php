<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/auth.php';

requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$role   = $_SESSION['user_role'];

// Upload dirs
$attachDir    = __DIR__ . '/../assets/uploads/task_attachments/';
$submitDir    = __DIR__ . '/../assets/uploads/submissions/';
$attachUrl    = '../assets/uploads/task_attachments/';
$submitUrl    = '../assets/uploads/submissions/';
$allowedTypes = ['application/pdf','application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation',
  'image/jpeg','image/png','image/gif','image/webp',
  'text/plain','text/csv','application/zip','application/x-zip-compressed'];
$maxSize = 20 * 1024 * 1024; 

function verifyMentorAccess($pdo, $userId, $menteeId) {
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE mentor_id = ? AND mentee_id = ? AND is_active = 1");
    $stmt->execute([$userId, $menteeId]);
    return $stmt->fetch();
}

function verifyTaskAccess($pdo, $userId, $taskId, $role) {
    if ($role === 'mentor') {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND mentor_id = ?");
        $stmt->execute([$taskId, $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND mentee_id = ?");
        $stmt->execute([$taskId, $userId]);
    }
    return $stmt->fetch();
}

switch ($action) {

    // ── Get tasks (mentor sees all they created; mentee sees their own) ─
    case 'get_tasks':
        $filter = $_GET['filter'] ?? 'all'; 
        if ($role === 'mentor') {
            $menteeId = (int)($_GET['mentee_id'] ?? 0);
            $sql = "SELECT t.*,
                           u.full_name AS mentee_name, u.profile_picture AS mentee_pic,
                           ts.status AS sub_status, ts.grade, ts.submitted_at,
                           (SELECT COUNT(*) FROM task_attachments ta WHERE ta.task_id = t.id) AS attach_count
                    FROM tasks t
                    JOIN users u ON u.id = t.mentee_id
                    LEFT JOIN task_submissions ts ON ts.task_id = t.id AND ts.mentee_id = t.mentee_id
                    WHERE t.mentor_id = ?";
            $params = [$userId];
            if ($menteeId) { $sql .= " AND t.mentee_id = ?"; $params[] = $menteeId; }
            $sql .= " ORDER BY t.created_at DESC";
        } else {
            $sql = "SELECT t.*,
                           u.full_name AS mentor_name, u.profile_picture AS mentor_pic,
                           ts.id AS sub_id, ts.status AS sub_status, ts.grade,
                           ts.feedback, ts.submitted_at, ts.submission_text,
                           ts.file_name AS sub_file_name, ts.file_path AS sub_file_path,
                           (SELECT COUNT(*) FROM task_attachments ta WHERE ta.task_id = t.id) AS attach_count
                    FROM tasks t
                    JOIN users u ON u.id = t.mentor_id
                    LEFT JOIN task_submissions ts ON ts.task_id = t.id AND ts.mentee_id = ?
                    WHERE t.mentee_id = ? AND t.status = 'published'
                    ORDER BY t.due_date ASC, t.created_at DESC";
            $params = [$userId, $userId];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        
        foreach ($tasks as &$t) {
            $picKey = $role === 'mentor' ? 'mentee_pic' : 'mentor_pic';
            $nameKey = $role === 'mentor' ? 'mentee_name' : 'mentor_name';
            $t['avatar'] = getAvatarUrl($t[$picKey] ?? null, $t[$nameKey] ?? 'User');
            $t['is_overdue'] = $t['due_date'] && strtotime($t['due_date']) < time()
                               && (!isset($t['sub_status']) || !in_array($t['sub_status'], ['submitted','graded']));
           
            $aStmt = $pdo->prepare("SELECT * FROM task_attachments WHERE task_id = ?");
            $aStmt->execute([$t['id']]);
            $t['attachments'] = $aStmt->fetchAll();
        }
        jsonResponse(['success' => true, 'tasks' => $tasks]);

    // ── Get single task detail
    case 'get_task':
        $taskId = (int)($_GET['task_id'] ?? 0);
        $task = verifyTaskAccess($pdo, $userId, $taskId, $role);
        if (!$task) jsonResponse(['success' => false, 'message' => 'Task not found or access denied'], 403);

        $aStmt = $pdo->prepare("SELECT * FROM task_attachments WHERE task_id = ?");
        $aStmt->execute([$taskId]);
        $task['attachments'] = $aStmt->fetchAll();

        if ($role === 'mentor') {
            
            $sStmt = $pdo->prepare("SELECT ts.*, u.full_name, u.profile_picture FROM task_submissions ts JOIN users u ON u.id = ts.mentee_id WHERE ts.task_id = ?");
            $sStmt->execute([$taskId]);
            $task['submissions'] = $sStmt->fetchAll();
        } else {
            $sStmt = $pdo->prepare("SELECT * FROM task_submissions WHERE task_id = ? AND mentee_id = ?");
            $sStmt->execute([$taskId, $userId]);
            $task['my_submission'] = $sStmt->fetch();
        }
        jsonResponse(['success' => true, 'task' => $task]);

    // ── Create task (mentor only)
    case 'create_task':
        if ($role !== 'mentor') jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $data     = $_POST;
        $menteeId = (int)($data['mentee_id'] ?? 0);
        $title    = sanitize($data['title'] ?? '');
        $desc     = sanitize($data['description'] ?? '');
        $dueDate  = $data['due_date'] ?? null;
        $points   = $data['points'] ? (int)$data['points'] : null;

        if (!$menteeId || !$title) jsonResponse(['success' => false, 'message' => 'Mentee and title are required']);

        $assign = verifyMentorAccess($pdo, $userId, $menteeId);
        if (!$assign) jsonResponse(['success' => false, 'message' => 'No active assignment with this mentee'], 403);

        $stmt = $pdo->prepare("INSERT INTO tasks (assignment_id, mentor_id, mentee_id, title, description, due_date, points) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$assign['id'], $userId, $menteeId, $title, $desc, $dueDate ?: null, $points]);
        $taskId = $pdo->lastInsertId();

      
        if (!empty($_FILES['attachments']['name'][0])) {
            if (!is_dir($attachDir)) mkdir($attachDir, 0755, true);
            foreach ($_FILES['attachments']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['attachments']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $origName = $_FILES['attachments']['name'][$idx];
                $size     = $_FILES['attachments']['size'][$idx];
                $type     = $_FILES['attachments']['type'][$idx];
                if ($size > $maxSize) continue;
                $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $safeName = 'task_' . $taskId . '_' . time() . '_' . $idx . '.' . $ext;
                move_uploaded_file($tmpName, $attachDir . $safeName);
                $aStmt = $pdo->prepare("INSERT INTO task_attachments (task_id, file_name, file_path, file_size, file_type) VALUES (?,?,?,?,?)");
                $aStmt->execute([$taskId, $origName, $safeName, $size, $type]);
            }
        }

      
        $stmt = $pdo->prepare("INSERT INTO task_submissions (task_id, mentee_id, status) VALUES (?,?,'not_submitted')");
        $stmt->execute([$taskId, $menteeId]);

        logActivity($userId, 'Task Created', "Created task: $title for mentee ID $menteeId");
        jsonResponse(['success' => true, 'message' => 'Task created successfully', 'task_id' => $taskId]);

    // ── Update task 
    case 'update_task':
        if ($role !== 'mentor') jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $taskId = (int)($data['task_id'] ?? 0);
        $task   = verifyTaskAccess($pdo, $userId, $taskId, $role);
        if (!$task) jsonResponse(['success' => false, 'message' => 'Task not found'], 404);

        $title   = sanitize($data['title'] ?? $task['title']);
        $desc    = sanitize($data['description'] ?? '');
        $dueDate = $data['due_date'] ?? null;
        $points  = isset($data['points']) && $data['points'] !== '' ? (int)$data['points'] : null;
        $status  = in_array($data['status'] ?? '', ['draft','published','archived']) ? $data['status'] : $task['status'];

        $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, due_date=?, points=?, status=? WHERE id=?");
        $stmt->execute([$title, $desc, $dueDate ?: null, $points, $status, $taskId]);
        jsonResponse(['success' => true, 'message' => 'Task updated successfully']);

    // ── Delete task 
    case 'delete_task':
        if ($role !== 'mentor') jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $taskId = (int)($data['task_id'] ?? 0);
        $task   = verifyTaskAccess($pdo, $userId, $taskId, $role);
        if (!$task) jsonResponse(['success' => false, 'message' => 'Task not found'], 404);

        // Delete physical attachment files
        $aStmt = $pdo->prepare("SELECT file_path FROM task_attachments WHERE task_id = ?");
        $aStmt->execute([$taskId]);
        foreach ($aStmt->fetchAll() as $f) { @unlink($attachDir . $f['file_path']); }

        // Delete submission files
        $sStmt = $pdo->prepare("SELECT file_path FROM task_submissions WHERE task_id = ? AND file_path IS NOT NULL");
        $sStmt->execute([$taskId]);
        foreach ($sStmt->fetchAll() as $f) { @unlink($submitDir . $f['file_path']); }

        $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);
        jsonResponse(['success' => true, 'message' => 'Task deleted']);

    // ── Submit task (mentee) 
    case 'submit_task':
        if ($role !== 'mentee') jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $taskId = (int)($_POST['task_id'] ?? 0);
        $text   = sanitize($_POST['submission_text'] ?? '');
        $task   = verifyTaskAccess($pdo, $userId, $taskId, $role);
        if (!$task) jsonResponse(['success' => false, 'message' => 'Task not found'], 404);

        $fileName = null; $filePath = null; $fileSize = null; $fileType = null;

        // Handle file
        if (!empty($_FILES['submission_file']['name'])) {
            if (!is_dir($submitDir)) mkdir($submitDir, 0755, true);
            $file = $_FILES['submission_file'];
            if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxSize) {
                $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $safeName = 'sub_' . $taskId . '_' . $userId . '_' . time() . '.' . $ext;
                move_uploaded_file($file['tmp_name'], $submitDir . $safeName);
                $fileName = $file['name'];
                $filePath = $safeName;
                $fileSize = $file['size'];
                $fileType = $file['type'];
            }
        }

        $stmt = $pdo->prepare("INSERT INTO task_submissions (task_id, mentee_id, submission_text, file_name, file_path, file_size, file_type, status, submitted_at)
                               VALUES (?,?,?,?,?,?,?,'submitted', NOW())
                               ON DUPLICATE KEY UPDATE submission_text=VALUES(submission_text), file_name=COALESCE(VALUES(file_name),file_name), file_path=COALESCE(VALUES(file_path),file_path), file_size=COALESCE(VALUES(file_size),file_size), file_type=COALESCE(VALUES(file_type),file_type), status='submitted', submitted_at=NOW()");
        $stmt->execute([$taskId, $userId, $text ?: null, $fileName, $filePath, $fileSize, $fileType]);

        logActivity($userId, 'Task Submitted', "Submitted task ID $taskId");
        jsonResponse(['success' => true, 'message' => 'Assignment submitted successfully!']);

    // ── Grade submission (mentor) 
    case 'grade_submission':
        if ($role !== 'mentor') jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $taskId = (int)($data['task_id'] ?? 0);
        $menteeId = (int)($data['mentee_id'] ?? 0);
        $grade  = isset($data['grade']) && $data['grade'] !== '' ? (int)$data['grade'] : null;
        $feedback = sanitize($data['feedback'] ?? '');

        $task = verifyTaskAccess($pdo, $userId, $taskId, $role);
        if (!$task) jsonResponse(['success' => false, 'message' => 'Task not found'], 404);

        $stmt = $pdo->prepare("UPDATE task_submissions SET grade=?, feedback=?, status='graded', graded_at=NOW() WHERE task_id=? AND mentee_id=?");
        $stmt->execute([$grade, $feedback ?: null, $taskId, $menteeId]);
        jsonResponse(['success' => true, 'message' => 'Submission graded successfully']);

    // ── Download attachment 
    case 'download_attachment':
        $attachId = (int)($_GET['attach_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT ta.*, t.mentor_id, t.mentee_id FROM task_attachments ta JOIN tasks t ON t.id = ta.task_id WHERE ta.id = ?");
        $stmt->execute([$attachId]);
        $file = $stmt->fetch();
        if (!$file || ($file['mentor_id'] != $userId && $file['mentee_id'] != $userId)) {
            jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        $path = $attachDir . $file['file_path'];
        if (!file_exists($path)) jsonResponse(['success' => false, 'message' => 'File not found'], 404);
        header('Content-Type: ' . ($file['file_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($file['file_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    // ── Download submission file 
    case 'download_submission':
        $subId = (int)($_GET['sub_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT ts.*, t.mentor_id, t.mentee_id FROM task_submissions ts JOIN tasks t ON t.id = ts.task_id WHERE ts.id = ?");
        $stmt->execute([$subId]);
        $sub = $stmt->fetch();
        if (!$sub || ($sub['mentor_id'] != $userId && $sub['mentee_id'] != $userId)) {
            jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }
        if (!$sub['file_path']) jsonResponse(['success' => false, 'message' => 'No file attached'], 404);
        $path = $submitDir . $sub['file_path'];
        if (!file_exists($path)) jsonResponse(['success' => false, 'message' => 'File not found'], 404);
        header('Content-Type: ' . ($sub['file_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . addslashes($sub['file_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;

    // ── Get summary stats 
    case 'get_stats':
        if ($role === 'mentor') {
            $total    = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE mentor_id = ? AND status='published'");
            $total->execute([$userId]); $t = $total->fetchColumn();
            $graded   = $pdo->prepare("SELECT COUNT(*) FROM task_submissions ts JOIN tasks t ON t.id=ts.task_id WHERE t.mentor_id=? AND ts.status='graded'");
            $graded->execute([$userId]); $g = $graded->fetchColumn();
            $pending  = $pdo->prepare("SELECT COUNT(*) FROM task_submissions ts JOIN tasks t ON t.id=ts.task_id WHERE t.mentor_id=? AND ts.status='submitted'");
            $pending->execute([$userId]); $p = $pending->fetchColumn();
            jsonResponse(['success' => true, 'stats' => ['total' => $t, 'graded' => $g, 'pending_review' => $p]]);
        } else {
            $total    = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE mentee_id=? AND status='published'");
            $total->execute([$userId]); $t = $total->fetchColumn();
            $submitted = $pdo->prepare("SELECT COUNT(*) FROM task_submissions WHERE mentee_id=? AND status IN ('submitted','graded')");
            $submitted->execute([$userId]); $s = $submitted->fetchColumn();
            $graded   = $pdo->prepare("SELECT COUNT(*) FROM task_submissions WHERE mentee_id=? AND status='graded'");
            $graded->execute([$userId]); $g = $graded->fetchColumn();
            jsonResponse(['success' => true, 'stats' => ['total' => $t, 'submitted' => $s, 'graded' => $g, 'pending' => $t - $s]]);
        }
        break;

    // ── Get mentees for mentor 
    case 'get_my_mentees':
        if ($role !== 'mentor') jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.email, u.profile_picture, a.id as assignment_id FROM assignments a JOIN users u ON u.id=a.mentee_id WHERE a.mentor_id=? AND a.is_active=1");
        $stmt->execute([$userId]);
        $mentees = $stmt->fetchAll();
        foreach ($mentees as &$m) { $m['avatar'] = getAvatarUrl($m['profile_picture'], $m['full_name']); }
        jsonResponse(['success' => true, 'mentees' => $mentees]);

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
?>
