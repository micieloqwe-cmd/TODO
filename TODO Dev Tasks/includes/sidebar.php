<?php
// ==========================================
// includes/sidebar.php
// ==========================================

$user      = currentUser();
$initials  = mb_strtoupper(mb_substr($user['name'], 0, 1));
$today     = date('Y-m-d');

// Badge count: pending todos
$stmtBadge = $pdo->prepare("SELECT COUNT(*) FROM todos WHERE user_id=? AND status='pending'");
$stmtBadge->execute([(int)$user['id']]);
$pendingCount = (int)$stmtBadge->fetchColumn();

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-logo">Task<span>Flow</span></div>

  <div class="sidebar-section-label">เมนูหลัก</div>
  <nav class="sidebar-nav">
    <a href="index.php" class="<?= $currentPage==='index.php'?'active':'' ?>">
      <span class="icon">📋</span>
      รายการงาน
      <?php if ($pendingCount > 0): ?>
        <span class="badge"><?= $pendingCount > 99 ? '99+' : $pendingCount ?></span>
      <?php endif; ?>
    </a>
    <a href="dashboard.php" class="<?= $currentPage==='dashboard.php'?'active':'' ?>">
      <span class="icon">📊</span>
      Dashboard
    </a>
  </nav>

  <?php if ($user['role'] === 'admin'): ?>
  <div class="sidebar-section-label" style="margin-top:12px">Admin</div>
  <nav class="sidebar-nav">
    <a href="admin/index.php" style="color:var(--accent3)">
      <span class="icon">⭐</span>
      Admin Panel
    </a>
  </nav>
  <?php endif; ?>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= $initials ?></div>
      <div class="sidebar-user-info">
        <div class="name"><?= sanitize($user['name']) ?></div>
        <div class="role"><?= $user['role'] === 'admin' ? '⭐ Admin' : 'ผู้ใช้งาน' ?></div>
      </div>
    </div>
    <a href="logout.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
      ออกจากระบบ
    </a>
  </div>
</aside>
