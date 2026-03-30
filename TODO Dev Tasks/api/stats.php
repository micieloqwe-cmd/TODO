<?php
// ==========================================
// api/stats.php — User Stats API
// ==========================================

require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$uid = (int)currentUser()['id'];
$today = date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'done') AS done,
        SUM(status = 'pending') AS pending,
        SUM(status = 'pending' AND due_date IS NOT NULL AND due_date < ?) AS overdue
    FROM todos WHERE user_id = ?
");
$stmt->execute([$today, $uid]);
$stats = $stmt->fetch();

// Priority breakdown
$stmtP = $pdo->prepare("
    SELECT priority, COUNT(*) as cnt FROM todos WHERE user_id = ? GROUP BY priority
");
$stmtP->execute([$uid]);
$priorityRows = $stmtP->fetchAll();
$priority = ['low' => 0, 'medium' => 0, 'high' => 0];
foreach ($priorityRows as $r) $priority[$r['priority']] = (int)$r['cnt'];

jsonResponse([
    'success' => true,
    'stats' => [
        'total'    => (int)$stats['total'],
        'done'     => (int)$stats['done'],
        'pending'  => (int)$stats['pending'],
        'overdue'  => (int)$stats['overdue'],
        'priority' => $priority,
    ]
]);
