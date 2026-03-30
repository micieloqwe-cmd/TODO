<?php
// ==========================================
// api/todo_action.php — CRUD API for Todos
// ==========================================

require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user = currentUser();
$uid  = (int)$user['id'];

// --- Parse input ---
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$action = $_GET['action'] ?? $input['action'] ?? '';

// --- CSRF verification for mutations ---
$mutating = in_array($action, ['create', 'update', 'delete', 'toggle']);
if ($mutating) {
    $token = $input['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
}

switch ($action) {

    // ----- GET single todo -----
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        $todo = $stmt->fetch();
        if (!$todo) jsonResponse(['success' => false, 'message' => 'Not found'], 404);
        jsonResponse(['success' => true, 'todo' => $todo]);
        break;

    // ----- CREATE -----
    case 'create':
        $title       = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $priority    = in_array($input['priority'] ?? '', ['low','medium','high']) ? $input['priority'] : 'medium';
        $due_date    = !empty($input['due_date']) ? $input['due_date'] : null;

        if (empty($title)) jsonResponse(['success' => false, 'message' => 'กรุณากรอกชื่องาน']);
        if (mb_strlen($title) > 255) jsonResponse(['success' => false, 'message' => 'ชื่องานยาวเกินไป']);
        if ($due_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) $due_date = null;

        $stmt = $pdo->prepare("INSERT INTO todos (user_id, title, description, priority, due_date) VALUES (?,?,?,?,?)");
        $stmt->execute([$uid, $title, $description, $priority, $due_date]);
        jsonResponse(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    // ----- UPDATE -----
    case 'update':
        $id          = (int)($input['id'] ?? 0);
        $title       = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $priority    = in_array($input['priority'] ?? '', ['low','medium','high']) ? $input['priority'] : 'medium';
        $due_date    = !empty($input['due_date']) ? $input['due_date'] : null;

        if (empty($title)) jsonResponse(['success' => false, 'message' => 'กรุณากรอกชื่องาน']);

        // Verify ownership
        $stmt = $pdo->prepare("SELECT id FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) jsonResponse(['success' => false, 'message' => 'ไม่พบงาน'], 404);

        $stmt = $pdo->prepare("UPDATE todos SET title=?, description=?, priority=?, due_date=? WHERE id=? AND user_id=?");
        $stmt->execute([$title, $description, $priority, $due_date, $id, $uid]);
        jsonResponse(['success' => true]);
        break;

    // ----- TOGGLE STATUS -----
    case 'toggle':
        $id     = (int)($input['id'] ?? 0);
        $status = in_array($input['status'] ?? '', ['pending','done']) ? $input['status'] : 'pending';

        $stmt = $pdo->prepare("SELECT id FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) jsonResponse(['success' => false, 'message' => 'ไม่พบงาน'], 404);

        $stmt = $pdo->prepare("UPDATE todos SET status=? WHERE id=? AND user_id=?");
        $stmt->execute([$status, $id, $uid]);
        jsonResponse(['success' => true, 'status' => $status]);
        break;

    // ----- DELETE -----
    case 'delete':
        $id = (int)($input['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT id FROM todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $uid]);
        if (!$stmt->fetch()) jsonResponse(['success' => false, 'message' => 'ไม่พบงาน'], 404);

        $stmt = $pdo->prepare("DELETE FROM todos WHERE id=? AND user_id=?");
        $stmt->execute([$id, $uid]);
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}
