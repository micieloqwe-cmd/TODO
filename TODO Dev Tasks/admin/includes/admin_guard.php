<?php
// ==========================================
// admin/includes/admin_guard.php
// Middleware: ตรวจสอบว่าเป็น Admin เท่านั้น
// ==========================================

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

function requireAdmin(): void {
    if (!isLoggedIn()) {
        header('Location: ../../login.php');
        exit;
    }
    $user = currentUser();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Admin stats helper
function getSystemStats(PDO $pdo): array {
    $today = date('Y-m-d');

    $row = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM users)                          AS total_users,
            (SELECT COUNT(*) FROM users WHERE role='admin')       AS total_admins,
            (SELECT COUNT(*) FROM todos)                          AS total_todos,
            (SELECT COUNT(*) FROM todos WHERE status='done')      AS done_todos,
            (SELECT COUNT(*) FROM todos WHERE status='pending')   AS pending_todos,
            (SELECT COUNT(*) FROM todos
             WHERE status='pending' AND due_date IS NOT NULL
               AND due_date < '$today')                           AS overdue_todos
    ")->fetch();

    return $row;
}
