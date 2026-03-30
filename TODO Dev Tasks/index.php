<?php
// ==========================================
// index.php — Main Todo List Page
// ==========================================

require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

$user  = currentUser();
$uid   = (int)$user['id'];
$today = date('Y-m-d');
$csrf  = generateCsrfToken();

// --- Filters ---
$search   = trim($_GET['search']   ?? '');
$status   = in_array($_GET['status']   ?? '', ['pending','done'])           ? $_GET['status']   : '';
$priority = in_array($_GET['priority'] ?? '', ['low','medium','high'])       ? $_GET['priority'] : '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = ITEMS_PER_PAGE;
$offset   = ($page - 1) * $limit;

// --- Build query ---
$where  = ["t.user_id = :uid"];
$params = [':uid' => $uid];

if ($search)   { $where[] = "t.title LIKE :search";  $params[':search']   = "%$search%"; }
if ($status)   { $where[] = "t.status = :status";    $params[':status']   = $status; }
if ($priority) { $where[] = "t.priority = :priority"; $params[':priority'] = $priority; }

$whereSQL = implode(' AND ', $where);

// Total count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM todos t WHERE $whereSQL");
$stmtCount->execute($params);
$totalItems = (int)$stmtCount->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));
if ($page > $totalPages) $page = $totalPages;

// Fetch todos
$stmt = $pdo->prepare("
    SELECT * FROM todos t
    WHERE $whereSQL
    ORDER BY
        CASE WHEN status='pending' THEN 0 ELSE 1 END,
        CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END,
        due_date IS NULL, due_date ASC,
        created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$todos = $stmt->fetchAll();

// Stats
$stmtStat = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status='done') AS done,
        SUM(status='pending') AS pending,
        SUM(status='pending' AND due_date IS NOT NULL AND due_date < :today) AS overdue
    FROM todos WHERE user_id = :uid
");
$stmtStat->execute([':today' => $today, ':uid' => $uid]);
$stats = $stmtStat->fetch();
$pct   = $stats['total'] > 0 ? round(($stats['done'] / $stats['total']) * 100) : 0;

// Priority labels
$priorityLabel = ['high' => 'สูง', 'medium' => 'กลาง', 'low' => 'ต่ำ'];
$priorityTag   = ['high' => 'tag-priority-high', 'medium' => 'tag-priority-medium', 'low' => 'tag-priority-low'];

function dateLabel(string $date, string $today): string {
    $diff = (strtotime($date) - strtotime($today)) / 86400;
    if ($diff < 0)  return 'overdue';
    if ($diff == 0) return 'today';
    if ($diff == 1) return 'tomorrow';
    return 'future';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf" content="<?= $csrf ?>">
  <title>รายการงาน — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-layout">
  <!-- SIDEBAR -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- MAIN -->
  <main class="main-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">รายการงานของฉัน</h1>
        <p class="page-subtitle">
          <?= date('l, d F Y') ?> &nbsp;·&nbsp; รวม <?= number_format($stats['total']) ?> งาน
        </p>
      </div>
      <button class="btn btn-primary" onclick="Modal.open('modal-add')">
        + เพิ่มงานใหม่
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">งานทั้งหมด</div>
        <div class="stat-value" id="stat-total"><?= $stats['total'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">เสร็จแล้ว</div>
        <div class="stat-value green" id="stat-done"><?= $stats['done'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">ยังไม่เสร็จ</div>
        <div class="stat-value orange" id="stat-pending"><?= $stats['pending'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">เกินกำหนด</div>
        <div class="stat-value red" id="stat-overdue"><?= $stats['overdue'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
    </div>

    <!-- Progress -->
    <div class="progress-wrap">
      <div class="progress-header">
        <span class="progress-label">ความคืบหน้าโดยรวม</span>
        <span class="progress-pct" id="stat-pct"><?= $pct ?>%</span>
      </div>
      <div class="progress-bar-bg">
        <div class="progress-bar-fill" id="progress-fill" data-pct="<?= $pct ?>" style="width:0%"></div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="filter-bar">
      <input type="text" id="filter-search" placeholder="🔍 ค้นหางาน..."
             value="<?= sanitize($search) ?>">
      <select id="filter-status" onchange="applyFilters()">
        <option value="">สถานะทั้งหมด</option>
        <option value="pending" <?= $status==='pending'?'selected':'' ?>>⏳ ยังไม่เสร็จ</option>
        <option value="done"    <?= $status==='done'   ?'selected':'' ?>>✓ เสร็จแล้ว</option>
      </select>
      <select id="filter-priority" onchange="applyFilters()">
        <option value="">ความสำคัญทั้งหมด</option>
        <option value="high"   <?= $priority==='high'  ?'selected':'' ?>>🔴 สูง</option>
        <option value="medium" <?= $priority==='medium'?'selected':'' ?>>🟡 กลาง</option>
        <option value="low"    <?= $priority==='low'   ?'selected':'' ?>>🟢 ต่ำ</option>
      </select>
      <?php if ($search || $status || $priority): ?>
        <a href="index.php" class="btn btn-secondary btn-sm">✕ ล้างตัวกรอง</a>
      <?php endif; ?>
    </div>

    <!-- Todo list -->
    <?php if (empty($todos)): ?>
      <div class="empty-state">
        <div class="icon">📋</div>
        <h3><?= ($search || $status || $priority) ? 'ไม่พบงานที่ตรงกับการค้นหา' : 'ยังไม่มีงาน' ?></h3>
        <p><?= ($search || $status || $priority) ? 'ลองเปลี่ยนตัวกรอง' : 'กดปุ่ม "+ เพิ่มงานใหม่" เพื่อเริ่มต้น' ?></p>
      </div>
    <?php else: ?>
      <div class="todo-list">
        <?php foreach ($todos as $i => $todo):
          $isDone    = $todo['status'] === 'done';
          $hasDate   = !empty($todo['due_date']);
          $dateClass = $hasDate ? dateLabel($todo['due_date'], $today) : '';
          $dateText  = $hasDate ? date('d M Y', strtotime($todo['due_date'])) : '';
          $dateLabels = ['overdue'=>'เกินกำหนด','today'=>'วันนี้','tomorrow'=>'พรุ่งนี้','future'=>$dateText];
        ?>
        <div class="todo-item priority-<?= $todo['priority'] ?> <?= $isDone ? 'done' : '' ?>"
             data-id="<?= $todo['id'] ?>"
             style="animation-delay: <?= $i * 0.04 ?>s">

          <input type="checkbox" class="todo-check"
                 <?= $isDone ? 'checked' : '' ?>
                 onchange="toggleStatus(<?= $todo['id'] ?>, this)"
                 title="กดเพื่อเปลี่ยนสถานะ">

          <div class="todo-body">
            <div class="todo-title"><?= sanitize($todo['title']) ?></div>
            <div class="todo-meta">
              <span class="tag <?= $priorityTag[$todo['priority']] ?>">
                <?= $todo['priority']==='high'?'🔴':($todo['priority']==='medium'?'🟡':'🟢') ?>
                <?= $priorityLabel[$todo['priority']] ?>
              </span>
              <span class="tag tag-status tag-status-<?= $todo['status'] ?>">
                <?= $isDone ? '✓ เสร็จแล้ว' : '⏳ ยังไม่เสร็จ' ?>
              </span>
              <?php if ($hasDate): ?>
                <span class="tag tag-date <?= $dateClass ?>">
                  📅 <?= $dateClass==='overdue'?'เกินกำหนด ':($dateClass==='today'?'วันนี้':($dateClass==='tomorrow'?'พรุ่งนี้':$dateText)) ?>
                  <?= ($dateClass==='overdue'||$dateClass==='today'||$dateClass==='tomorrow') ? "($dateText)" : '' ?>
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div class="todo-actions">
            <button class="btn btn-secondary btn-icon btn-sm"
                    onclick="openEdit(<?= $todo['id'] ?>)"
                    title="แก้ไข">✏️</button>
            <button class="btn btn-danger btn-icon btn-sm"
                    onclick="confirmDelete(<?= $todo['id'] ?>, '<?= addslashes(sanitize($todo['title'])) ?>')"
                    title="ลบ">🗑</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1):
        $baseParams = http_build_query(array_filter([
          'search'   => $search,
          'status'   => $status,
          'priority' => $priority,
        ]));
      ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?= $baseParams ?>&page=<?= $page-1 ?>">‹</a>
        <?php else: ?>
          <span class="disabled">‹</span>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++):
          if ($p === $page): ?>
            <span class="active"><?= $p ?></span>
          <?php elseif (abs($p - $page) <= 2 || $p === 1 || $p === $totalPages): ?>
            <a href="?<?= $baseParams ?>&page=<?= $p ?>"><?= $p ?></a>
          <?php elseif (abs($p - $page) === 3): ?>
            <span class="disabled">…</span>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?<?= $baseParams ?>&page=<?= $page+1 ?>">›</a>
        <?php else: ?>
          <span class="disabled">›</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>
</div>

<!-- ====== MODAL: ADD ====== -->
<div class="modal-overlay" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">➕ เพิ่มงานใหม่</h3>
      <button class="modal-close" onclick="Modal.close('modal-add')">✕</button>
    </div>
    <form id="form-add" onsubmit="saveAdd(event)" novalidate>
      <div class="form-group">
        <label for="add-title">ชื่องาน *</label>
        <input type="text" id="add-title" placeholder="เช่น เขียนรายงาน, ประชุมทีม" maxlength="255" required>
      </div>
      <div class="form-group">
        <label for="add-desc">รายละเอียด</label>
        <textarea id="add-desc" placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label for="add-priority">ความสำคัญ</label>
          <select id="add-priority">
            <option value="low">🟢 ต่ำ</option>
            <option value="medium" selected>🟡 กลาง</option>
            <option value="high">🔴 สูง</option>
          </select>
        </div>
        <div class="form-group">
          <label for="add-due">กำหนดส่ง</label>
          <input type="date" id="add-due" min="<?= $today ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-add')">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">เพิ่มงาน</button>
      </div>
    </form>
  </div>
</div>

<!-- ====== MODAL: EDIT ====== -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">✏️ แก้ไขงาน</h3>
      <button class="modal-close" onclick="Modal.close('modal-edit')">✕</button>
    </div>
    <form id="form-edit" onsubmit="saveEdit(event)" novalidate>
      <input type="hidden" id="edit-id">
      <div class="form-group">
        <label for="edit-title">ชื่องาน *</label>
        <input type="text" id="edit-title" maxlength="255" required>
      </div>
      <div class="form-group">
        <label for="edit-desc">รายละเอียด</label>
        <textarea id="edit-desc"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label for="edit-priority">ความสำคัญ</label>
          <select id="edit-priority">
            <option value="low">🟢 ต่ำ</option>
            <option value="medium">🟡 กลาง</option>
            <option value="high">🔴 สูง</option>
          </select>
        </div>
        <div class="form-group">
          <label for="edit-due">กำหนดส่ง</label>
          <input type="date" id="edit-due">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-edit')">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">บันทึก</button>
      </div>
    </form>
  </div>
</div>

<!-- ====== MODAL: DELETE ====== -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h3 class="modal-title">🗑 ยืนยันการลบ</h3>
      <button class="modal-close" onclick="Modal.close('modal-delete')">✕</button>
    </div>
    <input type="hidden" id="delete-todo-id">
    <p style="color:var(--text-dim);font-size:14px">
      ต้องการลบงาน <strong style="color:var(--text)" id="delete-todo-title"></strong> ใช่หรือไม่?<br>
      <span style="color:var(--text-muted);font-size:13px">การกระทำนี้ไม่สามารถเรียกคืนได้</span>
    </p>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-delete')">ยกเลิก</button>
      <button type="button" class="btn btn-danger" onclick="deleteTodo()">ลบงาน</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="assets/js/app.js"></script>
</body>
</html>
