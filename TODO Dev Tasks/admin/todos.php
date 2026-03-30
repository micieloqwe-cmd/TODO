<?php
// ==========================================
// admin/todos.php — ดู Todo ของทุก User
// ==========================================

require_once 'includes/admin_guard.php';
requireAdmin();

$csrf  = generateCsrfToken();
$today = date('Y-m-d');
$flash = '';

// --- Filter ---
$search   = trim($_GET['search']   ?? '');
$status   = in_array($_GET['status']   ?? '', ['pending','done'])       ? $_GET['status']   : '';
$priority = in_array($_GET['priority'] ?? '', ['low','medium','high'])   ? $_GET['priority'] : '';
$userId   = (int)($_GET['user_id'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 15;
$offset   = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($search)   { $where[] = "t.title LIKE ?"; $params[] = "%$search%"; }
if ($status)   { $where[] = "t.status = ?";   $params[] = $status; }
if ($priority) { $where[] = "t.priority = ?"; $params[] = $priority; }
if ($userId)   { $where[] = "t.user_id = ?";  $params[] = $userId; }
$whereSQL = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM todos t WHERE $whereSQL");
$totalStmt->execute($params);
$totalItems = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

$stmt = $pdo->prepare("
    SELECT t.*, u.name AS user_name, u.email AS user_email
    FROM todos t JOIN users u ON u.id = t.user_id
    WHERE $whereSQL
    ORDER BY
        CASE WHEN t.status='pending' THEN 0 ELSE 1 END,
        CASE t.priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END,
        t.due_date IS NULL, t.due_date ASC, t.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $limit, $offset]);
$todos = $stmt->fetchAll();

// Users list for filter dropdown
$allUsers = $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll();

// Filter user info
$filterUser = null;
if ($userId) {
    $s = $pdo->prepare("SELECT id, name FROM users WHERE id=?");
    $s->execute([$userId]);
    $filterUser = $s->fetch();
}

$priorityLabel = ['high'=>'สูง','medium'=>'กลาง','low'=>'ต่ำ'];
$priorityTag   = ['high'=>'tag-priority-high','medium'=>'tag-priority-medium','low'=>'tag-priority-low'];

function dateLabel2(string $date, string $today): string {
    $diff = (strtotime($date) - strtotime($today)) / 86400;
    if ($diff < 0)  return 'overdue';
    if ($diff == 0) return 'today';
    return 'future';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf" content="<?= $csrf ?>">
  <title>Todo ทั้งระบบ — Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="app-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          Todo ทั้งระบบ
          <?php if ($filterUser): ?>
            <span style="font-size:16px;color:var(--accent);font-weight:500"> — <?= sanitize($filterUser['name']) ?></span>
          <?php endif; ?>
        </h1>
        <p class="page-subtitle">
          <?= number_format($totalItems) ?> รายการ
          <?php if ($filterUser): ?> · <a href="todos.php" style="color:var(--accent2);font-size:13px">ดูทั้งหมด</a><?php endif; ?>
        </p>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar" style="margin-bottom:20px">
      <form method="GET" id="filter-form" style="display:contents">
        <?php if ($userId): ?><input type="hidden" name="user_id" value="<?= $userId ?>"><?php endif; ?>
        <input type="text" name="search" id="filter-search" placeholder="🔍 ค้นหางาน..."
               value="<?= sanitize($search) ?>"
               style="flex:1;min-width:160px;background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-sm);padding:9px 14px;color:var(--text);font-size:13px;font-family:'DM Sans',sans-serif;outline:none">

        <select name="status" onchange="document.getElementById('filter-form').submit()"
                style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-sm);padding:9px 14px;color:var(--text);font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
          <option value="">สถานะทั้งหมด</option>
          <option value="pending" <?= $status==='pending'?'selected':'' ?>>⏳ ยังไม่เสร็จ</option>
          <option value="done"    <?= $status==='done'   ?'selected':'' ?>>✓ เสร็จแล้ว</option>
        </select>

        <select name="priority" onchange="document.getElementById('filter-form').submit()"
                style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-sm);padding:9px 14px;color:var(--text);font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
          <option value="">ความสำคัญทั้งหมด</option>
          <option value="high"   <?= $priority==='high'  ?'selected':'' ?>>🔴 สูง</option>
          <option value="medium" <?= $priority==='medium'?'selected':'' ?>>🟡 กลาง</option>
          <option value="low"    <?= $priority==='low'   ?'selected':'' ?>>🟢 ต่ำ</option>
        </select>

        <?php if (!$userId): ?>
        <select name="user_id" onchange="document.getElementById('filter-form').submit()"
                style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-sm);padding:9px 14px;color:var(--text);font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
          <option value="">Users ทั้งหมด</option>
          <?php foreach ($allUsers as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $userId==$u['id']?'selected':'' ?>><?= sanitize($u['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <button type="submit" class="btn btn-secondary btn-sm">ค้นหา</button>
        <?php if ($search || $status || $priority || $userId): ?>
          <a href="todos.php" class="btn btn-secondary btn-sm">✕ ล้าง</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Todo list -->
    <?php if (empty($todos)): ?>
      <div class="empty-state">
        <div class="icon">📋</div>
        <h3>ไม่พบรายการงาน</h3>
        <p>ลองเปลี่ยนตัวกรอง</p>
      </div>
    <?php else: ?>
      <div class="todo-list">
        <?php foreach ($todos as $i => $t):
          $isDone   = $t['status'] === 'done';
          $hasDate  = !empty($t['due_date']);
          $dClass   = $hasDate ? dateLabel2($t['due_date'], $today) : '';
          $dateText = $hasDate ? date('d M Y', strtotime($t['due_date'])) : '';
        ?>
        <div class="todo-item priority-<?= $t['priority'] ?> <?= $isDone?'done':'' ?>"
             style="animation-delay:<?= $i*0.03 ?>s">

          <!-- Readonly status indicator -->
          <div style="width:22px;height:22px;border-radius:6px;border:2px solid <?= $isDone?'var(--accent)':'var(--glass-border)' ?>;background:<?= $isDone?'var(--accent)':'transparent' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;color:#0d0f14">
            <?= $isDone ? '✓' : '' ?>
          </div>

          <div class="todo-body">
            <div class="todo-title"><?= sanitize($t['title']) ?></div>
            <div class="todo-meta">
              <!-- User badge -->
              <span class="tag" style="background:rgba(181,242,61,0.10);color:var(--accent2)">
                👤 <?= sanitize($t['user_name']) ?>
              </span>
              <span class="tag <?= $priorityTag[$t['priority']] ?>">
                <?= $t['priority']==='high'?'🔴':($t['priority']==='medium'?'🟡':'🟢') ?> <?= $priorityLabel[$t['priority']] ?>
              </span>
              <span class="tag tag-status-<?= $t['status'] ?>">
                <?= $isDone ? '✓ เสร็จแล้ว' : '⏳ ยังไม่เสร็จ' ?>
              </span>
              <?php if ($hasDate): ?>
                <span class="tag tag-date <?= $dClass ?>">
                  📅 <?= $dClass==='overdue'?'เกินกำหนด':($dClass==='today'?'วันนี้':$dateText) ?>
                  <?= ($dClass==='overdue'||$dClass==='today') ? "($dateText)" : '' ?>
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="todo-actions">
            <a href="users.php?search=<?= urlencode($t['user_email']) ?>" class="btn btn-secondary btn-icon btn-sm" title="ดู User">👤</a>
            <a href="todos.php?user_id=<?= $t['user_id'] ?>" class="btn btn-secondary btn-icon btn-sm" title="งานทั้งหมดของ User นี้">📋</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1):
        $bp = http_build_query(array_filter(['search'=>$search,'status'=>$status,'priority'=>$priority,'user_id'=>$userId?:null]));
      ?>
      <div class="pagination">
        <?php if ($page>1): ?><a href="?<?= $bp ?>&page=<?= $page-1 ?>">‹</a><?php else: ?><span class="disabled">‹</span><?php endif; ?>
        <?php for ($p=1;$p<=$totalPages;$p++): ?>
          <?php if ($p===$page): ?><span class="active"><?= $p ?></span>
          <?php elseif (abs($p-$page)<=2||$p===1||$p===$totalPages): ?><a href="?<?= $bp ?>&page=<?= $p ?>"><?= $p ?></a>
          <?php elseif (abs($p-$page)===3): ?><span class="disabled">…</span>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page<$totalPages): ?><a href="?<?= $bp ?>&page=<?= $page+1 ?>">›</a><?php else: ?><span class="disabled">›</span><?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="../assets/js/app.js"></script>
<script>
// Live search with debounce
let t;
document.getElementById('filter-search')?.addEventListener('input', () => {
  clearTimeout(t);
  t = setTimeout(() => document.getElementById('filter-form').submit(), 500);
});
</script>
</body>
</html>
