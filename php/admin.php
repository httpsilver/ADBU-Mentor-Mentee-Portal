<?php
require_once __DIR__ . '/../php/config.php';
require_once __DIR__ . '/../php/auth.php';

requireAdmin();

$pdo    = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── Dashboard stats 
    case 'get_stats':
        $stats = [];
        $stats['total_mentors']      = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mentor' AND is_active=1")->fetchColumn();
        $stats['total_mentees']      = $pdo->query("SELECT COUNT(*) FROM users WHERE role='mentee' AND is_active=1")->fetchColumn();
        $stats['active_assignments'] = $pdo->query("SELECT COUNT(*) FROM assignments WHERE is_active=1")->fetchColumn();
        $stats['total_messages']     = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        jsonResponse(['success' => true, 'stats' => $stats]);

    // ── Recent activity 
    case 'get_activity':
        $stmt = $pdo->query("
            SELECT al.*, u.full_name, u.role, u.profile_picture
            FROM activity_log al
            JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC LIMIT 15
        ");
        $logs = $stmt->fetchAll();
        foreach ($logs as &$l) { $l['avatar'] = getAvatarUrl($l['profile_picture'], $l['full_name']); }
        jsonResponse(['success' => true, 'activity' => $logs]);

    // ── Get all users 
    case 'get_users':
        $role = $_GET['role'] ?? null;
        if ($role && in_array($role, ['mentor', 'mentee'])) {
            $stmt = $pdo->prepare("SELECT id, full_name, email, role, profile_picture, is_active, is_online, created_at FROM users WHERE role = ? ORDER BY full_name");
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->query("SELECT id, full_name, email, role, profile_picture, is_active, is_online, created_at FROM users WHERE role != 'admin' ORDER BY role, full_name");
        }
        $users = $stmt->fetchAll();
        foreach ($users as &$u) { $u['avatar'] = getAvatarUrl($u['profile_picture'], $u['full_name']); }
        jsonResponse(['success' => true, 'users' => $users]);

    // ── Get all assignments 
    case 'get_assignments':
        $stmt = $pdo->query("
            SELECT a.*, 
                   mr.full_name as mentor_name, mr.email as mentor_email, mr.profile_picture as mentor_pic,
                   me.full_name as mentee_name, me.email as mentee_email, me.profile_picture as mentee_pic,
                   ad.full_name as assigned_by_name
            FROM assignments a
            JOIN users mr ON mr.id = a.mentor_id
            JOIN users me ON me.id = a.mentee_id
            JOIN users ad ON ad.id = a.assigned_by
            ORDER BY a.assigned_at DESC
        ");
        $assignments = $stmt->fetchAll();
        foreach ($assignments as &$a) {
            $a['mentor_avatar'] = getAvatarUrl($a['mentor_pic'], $a['mentor_name']);
            $a['mentee_avatar'] = getAvatarUrl($a['mentee_pic'], $a['mentee_name']);
        }
        jsonResponse(['success' => true, 'assignments' => $assignments]);

    // ── Create assignment 
    case 'create_assignment':
        $mentorId = (int)($data['mentor_id'] ?? 0);
        $menteeId = (int)($data['mentee_id'] ?? 0);
        if (!$mentorId || !$menteeId) jsonResponse(['success' => false, 'message' => 'Mentor and mentee required']);

        
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE mentor_id = ? AND mentee_id = ? AND is_active = 1");
        $stmt->execute([$mentorId, $menteeId]);
        if ($stmt->fetch()) jsonResponse(['success' => false, 'message' => 'This pair is already assigned']);

        
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE mentor_id = ? AND mentee_id = ? AND is_active = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$mentorId, $menteeId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $pdo->prepare("UPDATE assignments SET is_active = 1, assigned_by = ?, assigned_at = NOW() WHERE id = ?")
                ->execute([$_SESSION['user_id'], $existing['id']]);
            $assignId = $existing['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO assignments (mentor_id, mentee_id, assigned_by) VALUES (?, ?, ?)");
            $stmt->execute([$mentorId, $menteeId, $_SESSION['user_id']]);
            $assignId = $pdo->lastInsertId();

            
            $pdo->prepare("INSERT INTO conversations (assignment_id) VALUES (?)")->execute([$assignId]);
        }

        $mentor = $pdo->prepare("SELECT full_name FROM users WHERE id = ?"); $mentor->execute([$mentorId]);
        $mentorName = $mentor->fetchColumn();
        $mentee = $pdo->prepare("SELECT full_name FROM users WHERE id = ?"); $mentee->execute([$menteeId]);
        $menteeName = $mentee->fetchColumn();
        logActivity($_SESSION['user_id'], 'Assignment Created', "Assigned $mentorName to $menteeName");

        jsonResponse(['success' => true, 'message' => 'Assignment created successfully']);

    // ── Delete/deactivate assignment 
    case 'delete_assignment':
        $id = (int)($data['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID']);
        $stmt = $pdo->prepare("UPDATE assignments SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($_SESSION['user_id'], 'Assignment Removed', "Assignment ID $id deactivated");
        jsonResponse(['success' => true, 'message' => 'Assignment removed successfully']);

    // ── Restore a deactivated assignment 
    case 'restore_assignment':
        $id = (int)($data['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID']);
        $pdo->prepare("UPDATE assignments SET is_active = 1, assigned_at = NOW() WHERE id = ?")
            ->execute([$id]);
        logActivity($_SESSION['user_id'], 'Assignment Restored', "Assignment ID $id reactivated");
        jsonResponse(['success' => true, 'message' => 'Assignment restored successfully']);

    // ── Toggle user active status 
    case 'toggle_user':
        $id = (int)($data['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID']);
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'User status updated']);

    // ── Delete user 
    case 'delete_user':
        $id = (int)($data['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid ID']);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        jsonResponse(['success' => true, 'message' => 'User deleted successfully']);

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
?>
