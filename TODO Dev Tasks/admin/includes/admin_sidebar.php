<?php
// admin/includes/admin_sidebar.php
$user        = currentUser();
$initials    = mb_strtoupper(mb_substr($user['name'], 0, 1));
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    Task<span>Flow</span>
    <span style="display:block;font-size:11px;font-weight:600;color:var(--accent3);letter-spacing:1.5px;margin-top:2px">ADMIN</span>
  </div>

  <div class="sidebar-section-label">Admin Panel</div>
  <nav class="sidebar-nav">
    <a href="index.php" class="<?= $currentPage==='index.php'?'active':'' ?>">
      <span class="icon">📊</span> Dashboard ระบบ
    </a>
    <a href="users.php" class="<?= $currentPage==='users.php'?'active':'' ?>">
      <span class="icon">👥</span> จัดการ Users
    </a>
    <a href="todos.php" class="<?= $currentPage==='todos.php'?'active':'' ?>">
      <span class="icon">📋</span> Todo ทั้งระบบ
    </a>
  </nav>

  <div class="sidebar-section-label" style="margin-top:12px">ข้อมูล admin</div>
  <nav class="sidebar-nav">
    <a href="../index.php">
      <span class="icon">🏠</span> กลับหน้าหลัก
    </a>
    <a href="../dashboard.php">
      <span class="icon">📈</span> Dashboard ของฉัน
    </a>
  </nav>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar" style="background:linear-gradient(135deg,var(--accent3),var(--danger))"><?= $initials ?></div>
      <div class="sidebar-user-info">
        <div class="name"><?= sanitize($user['name']) ?></div>
        <div class="role" style="color:var(--accent3)">⭐ Administrator</div>
      </div>
    </div>
    <a href="../logout.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
      ออกจากระบบ
    </a>
  </div>
</aside>
