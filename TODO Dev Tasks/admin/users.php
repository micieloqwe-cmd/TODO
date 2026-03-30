<?php
// ==========================================
// admin/users.php — จัดการ Users ทั้งหมด
// ==========================================

require_once 'includes/admin_guard.php';
requireAdmin();

$csrf    = generateCsrfToken();
$flash   = '';
$flashType = 'success';

// --- Handle POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $flash = 'คำขอไม่ถูกต้อง'; $flashType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $target = (int)($_POST['user_id'] ?? 0);
        $me     = (int)currentUser()['id'];

        if ($target === $me) {
            $flash = 'ไม่สามารถจัดการบัญชีของตัวเองได้'; $flashType = 'error';
        } elseif ($action === 'delete') {
            // Check target exists
            $check = $pdo->prepare("SELECT id FROM users WHERE id=?");
            $check->execute([$target]);
            if ($check->fetch()) {
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$target]);
                $flash = 'ลบผู้ใช้เรียบร้อยแล้ว';
            } else {
                $flash = 'ไม่พบผู้ใช้'; $flashType = 'error';
            }
        } elseif ($action === 'change_role') {
            $role = in_array($_POST['role'] ?? '', ['user','admin']) ? $_POST['role'] : 'user';
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $target]);
            $flash = 'เปลี่ยน Role เรียบร้อยแล้ว';
        } elseif ($action === 'create') {
            $name     = trim($_POST['name']     ?? '');
            $email    = trim($_POST['email']    ?? '');
            $password = $_POST['password']      ?? '';
            $role     = in_array($_POST['role'] ?? '', ['user','admin']) ? $_POST['role'] : 'user';

            if (!$name || !$email || !$password) {
                $flash = 'กรุณากรอกข้อมูลให้ครบ'; $flashType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $flash = 'Email ไม่ถูกต้อง'; $flashType = 'error';
            } elseif (strlen($password) < 8) {
                $flash = 'Password ต้องมีอย่างน้อย 8 ตัวอักษร'; $flashType = 'error';
            } else {
                $dup = $pdo->prepare("SELECT id FROM users WHERE email=?");
                $dup->execute([$email]);
                if ($dup->fetch()) {
                    $flash = 'Email นี้มีในระบบแล้ว'; $flashType = 'error';
                } else {
                    $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
                        ->execute([$name, $email, hashPassword($password), $role]);
                    $flash = 'เพิ่ม User เรียบร้อยแล้ว';
                }
            }
        }
    }
}

// --- Filters & pagination ---
$search = trim($_GET['search'] ?? '');
$role   = in_array($_GET['role'] ?? '', ['user','admin']) ? $_GET['role'] : '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = "(u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($role)   { $where[] = "u.role = ?"; $params[] = $role; }
$whereSQL = implode(' AND ', $where);

$total     = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL");
$total->execute($params);
$totalItems = (int)$total->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $limit));

$stmt = $pdo->prepare("
    SELECT u.*,
           COUNT(t.id)            AS todo_count,
           SUM(t.status='done')   AS done_count,
           SUM(t.status='pending' AND t.due_date IS NOT NULL AND t.due_date < CURDATE()) AS overdue_count
    FROM users u
    LEFT JOIN todos t ON t.user_id = u.id
    WHERE $whereSQL
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([...$params, $limit, $offset]);
$users = $stmt->fetchAll();

$myId  = (int)currentUser()['id'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf" content="<?= $csrf ?>">
  <title>จัดการ Users — Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="app-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title">จัดการ Users</h1>
        <p class="page-subtitle">ผู้ใช้ทั้งหมด <?= number_format($totalItems) ?> คน</p>
      </div>
      <button class="btn btn-primary" onclick="Modal.open('modal-create')">+ เพิ่ม User</button>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flashType==='error'?'error':'success' ?>"><?= sanitize($flash) ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="filter-bar" style="margin-bottom:20px">
      <form method="GET" style="display:contents">
        <input type="text" name="search" placeholder="🔍 ค้นหาชื่อ หรือ Email..."
               value="<?= sanitize($search) ?>" style="flex:1;min-width:160px;background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-sm);padding:9px 14px;color:var(--text);font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
        <select name="role" onchange="this.form.submit()" style="background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-sm);padding:9px 14px;color:var(--text);font-size:13px;font-family:'DM Sans',sans-serif;outline:none">
          <option value="">Role ทั้งหมด</option>
          <option value="user"  <?= $role==='user' ?'selected':'' ?>>User</option>
          <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">ค้นหา</button>
        <?php if ($search || $role): ?>
          <a href="users.php" class="btn btn-secondary btn-sm">✕ ล้าง</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Table -->
    <div class="card" style="padding:0;overflow:hidden">
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>ผู้ใช้</th>
              <th>Email</th>
              <th>Role</th>
              <th style="text-align:center">งานทั้งหมด</th>
              <th style="text-align:center">เสร็จ</th>
              <th style="text-align:center">เกินกำหนด</th>
              <th>สมัครเมื่อ</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $i => $u):
              $isSelf = $u['id'] == $myId;
              $upct   = $u['todo_count'] > 0 ? round($u['done_count']/$u['todo_count']*100) : 0;
            ?>
            <tr class="<?= $isSelf?'row-self':'' ?>">
              <td style="color:var(--text-muted);font-size:12px"><?= $offset+$i+1 ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,<?= $u['role']==='admin'?'var(--accent3),var(--danger)':'var(--accent),var(--accent2)' ?>);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#0d0f14;flex-shrink:0">
                    <?= mb_strtoupper(mb_substr($u['name'],0,1)) ?>
                  </div>
                  <div>
                    <div style="font-size:13px;font-weight:500"><?= sanitize($u['name']) ?></div>
                    <?php if ($isSelf): ?><div style="font-size:10px;color:var(--accent)">(คุณ)</div><?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="font-size:13px;color:var(--text-muted)"><?= sanitize($u['email']) ?></td>
              <td>
                <?php if (!$isSelf): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <input type="hidden" name="action"   value="change_role">
                  <input type="hidden" name="user_id"  value="<?= $u['id'] ?>">
                  <select name="role" onchange="this.form.submit()" class="role-select <?= $u['role']==='admin'?'role-admin':'role-user' ?>">
                    <option value="user"  <?= $u['role']==='user' ?'selected':'' ?>>User</option>
                    <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                  </select>
                </form>
                <?php else: ?>
                  <span class="tag" style="background:rgba(242,169,61,0.15);color:var(--accent3)">⭐ Admin</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center">
                <a href="todos.php?user_id=<?= $u['id'] ?>" style="color:var(--accent);font-weight:600;text-decoration:none;font-size:14px"><?= $u['todo_count'] ?></a>
              </td>
              <td style="text-align:center">
                <span style="color:var(--accent2);font-size:13px"><?= $u['done_count'] ?> <span style="color:var(--text-muted);font-size:11px">(<?= $upct ?>%)</span></span>
              </td>
              <td style="text-align:center">
                <?php if ($u['overdue_count'] > 0): ?>
                  <span style="color:var(--danger);font-weight:600"><?= $u['overdue_count'] ?></span>
                <?php else: ?>
                  <span style="color:var(--text-muted)">—</span>
                <?php endif; ?>
              </td>
              <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="todos.php?user_id=<?= $u['id'] ?>" class="btn btn-secondary btn-icon btn-sm" title="ดู Todo">📋</a>
                  <?php if (!$isSelf): ?>
                  <button class="btn btn-danger btn-icon btn-sm"
                          onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= addslashes(sanitize($u['name'])) ?>')"
                          title="ลบ User">🗑</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
              <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted)">ไม่พบผู้ใช้</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): $bp = http_build_query(array_filter(['search'=>$search,'role'=>$role])); ?>
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

  </main>
</div>

<!-- Modal: Create User -->
<div class="modal-overlay" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <h3 class="modal-title">➕ เพิ่ม User ใหม่</h3>
      <button class="modal-close" onclick="Modal.close('modal-create')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="create">
      <div class="form-group">
        <label>ชื่อ-นามสกุล *</label>
        <input type="text" name="name" placeholder="สมชาย ใจดี" required>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" placeholder="user@example.com" required>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" placeholder="อย่างน้อย 8 ตัวอักษร" required>
        </div>
        <div class="form-group">
          <label>Role</label>
          <select name="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-create')">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">เพิ่ม User</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Delete User -->
<div class="modal-overlay" id="modal-del-user">
  <div class="modal" style="max-width:400px">
    <div class="modal-header">
      <h3 class="modal-title">🗑 ยืนยันการลบ User</h3>
      <button class="modal-close" onclick="Modal.close('modal-del-user')">✕</button>
    </div>
    <p style="color:var(--text-dim);font-size:14px">
      ต้องการลบ <strong style="color:var(--text)" id="del-user-name"></strong> ใช่หรือไม่?<br>
      <span style="color:var(--danger);font-size:13px">⚠ Todo ทั้งหมดของ User นี้จะถูกลบด้วย (CASCADE)</span>
    </p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="user_id" id="del-user-id">
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="Modal.close('modal-del-user')">ยกเลิก</button>
        <button type="submit" class="btn btn-danger">ลบ User</button>
      </div>
    </form>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="../assets/js/app.js"></script>
<script>
function confirmDeleteUser(id, name) {
  document.getElementById('del-user-name').textContent = name;
  document.getElementById('del-user-id').value = id;
  Modal.open('modal-del-user');
}
</script>
</body>
</html>
