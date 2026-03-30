<?php
// ==========================================
// admin/index.php — Admin Dashboard
// ==========================================

require_once 'includes/admin_guard.php';
requireAdmin();

$csrf  = generateCsrfToken();
$today = date('Y-m-d');
$stats = getSystemStats($pdo);
$pct   = $stats['total_todos'] > 0
    ? round(($stats['done_todos'] / $stats['total_todos']) * 100) : 0;

// Top 5 active users (by todo count)
$topUsers = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.created_at,
           COUNT(t.id) AS todo_count,
           SUM(t.status='done') AS done_count
    FROM users u
    LEFT JOIN todos t ON t.user_id = u.id
    GROUP BY u.id
    ORDER BY todo_count DESC
    LIMIT 5
")->fetchAll();

// Recent registrations
$recentUsers = $pdo->query("
    SELECT id, name, email, role, created_at FROM users
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// Recent todos (all users)
$recentTodos = $pdo->query("
    SELECT t.*, u.name AS user_name
    FROM todos t JOIN users u ON u.id = t.user_id
    ORDER BY t.created_at DESC LIMIT 6
")->fetchAll();

// Priority breakdown system-wide
$priRows = $pdo->query("
    SELECT priority, COUNT(*) cnt, SUM(status='done') done
    FROM todos GROUP BY priority
")->fetchAll();
$pri = ['high'=>['cnt'=>0,'done'=>0],'medium'=>['cnt'=>0,'done'=>0],'low'=>['cnt'=>0,'done'=>0]];
foreach ($priRows as $r) $pri[$r['priority']] = ['cnt'=>(int)$r['cnt'],'done'=>(int)$r['done']];

// Daily new todos last 7 days
$daily = $pdo->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM todos
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$dailyLabels = [];
$dailyValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('d/m', strtotime($d));
    $dailyValues[] = (int)($daily[$d] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf" content="<?= $csrf ?>">
  <title>Admin Dashboard — TaskFlow</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="assets/admin.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
  <?php include 'includes/admin_sidebar.php'; ?>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">ภาพรวมทั้งระบบ — <?= date('d F Y') ?></p>
      </div>
      <a href="users.php?action=create" class="btn btn-primary">+ เพิ่ม User</a>
    </div>

    <!-- System Stats -->
    <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
      <div class="stat-card" style="animation-delay:0s">
        <div class="stat-label">Users ทั้งหมด</div>
        <div class="stat-value accent"><?= $stats['total_users'] ?></div>
        <div class="stat-sub">รวม <?= $stats['total_admins'] ?> Admin</div>
      </div>
      <div class="stat-card" style="animation-delay:.06s">
        <div class="stat-label">Todo ทั้งระบบ</div>
        <div class="stat-value"><?= $stats['total_todos'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card" style="animation-delay:.12s">
        <div class="stat-label">เสร็จแล้ว</div>
        <div class="stat-value green"><?= $stats['done_todos'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card" style="animation-delay:.18s">
        <div class="stat-label">ยังไม่เสร็จ</div>
        <div class="stat-value orange"><?= $stats['pending_todos'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card" style="animation-delay:.24s">
        <div class="stat-label">เกินกำหนด</div>
        <div class="stat-value red"><?= $stats['overdue_todos'] ?></div>
        <div class="stat-sub">ต้องรีบจัดการ</div>
      </div>
    </div>

    <!-- Progress -->
    <div class="progress-wrap">
      <div class="progress-header">
        <span class="progress-label">Completion Rate ทั้งระบบ</span>
        <span class="progress-pct"><?= $pct ?>%</span>
      </div>
      <div class="progress-bar-bg">
        <div class="progress-bar-fill" id="progress-fill" data-pct="<?= $pct ?>" style="width:0%"></div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="dashboard-grid">
      <div class="card">
        <div class="card-title">งานใหม่ 7 วันล่าสุด</div>
        <canvas id="chart-daily" height="180"></canvas>
      </div>
      <div class="card">
        <div class="card-title">สัดส่วนสถานะ (ทั้งระบบ)</div>
        <?php if ($stats['total_todos'] > 0): ?>
          <canvas id="chart-status" height="180"></canvas>
        <?php else: ?>
          <div class="empty-state" style="padding:20px"><p>ยังไม่มีข้อมูล</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Bottom row -->
    <div class="dashboard-grid" style="margin-top:16px">

      <!-- Top users -->
      <div class="card">
        <div class="card-title">👑 Top Users (ตามจำนวนงาน)</div>
        <?php if (empty($topUsers)): ?>
          <div class="empty-state" style="padding:20px"><p>ยังไม่มีข้อมูล</p></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($topUsers as $i => $u):
            $upct = $u['todo_count'] > 0 ? round($u['done_count']/$u['todo_count']*100) : 0;
          ?>
          <div style="display:flex;align-items:center;gap:12px">
            <div style="width:24px;height:24px;border-radius:50%;background:var(--glass);border:1px solid var(--glass-border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--accent);flex-shrink:0"><?= $i+1 ?></div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($u['name']) ?></div>
              <div style="font-size:11px;color:var(--text-muted)"><?= $u['todo_count'] ?> งาน · <?= $upct ?>% เสร็จ</div>
            </div>
            <a href="todos.php?user_id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 10px">ดู</a>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Recent registrations -->
      <div class="card">
        <div class="card-title">🆕 สมัครสมาชิกล่าสุด</div>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($recentUsers as $u): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--glass);border-radius:var(--radius-sm)">
            <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#0d0f14;flex-shrink:0">
              <?= mb_strtoupper(mb_substr($u['name'],0,1)) ?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-size:13px;font-weight:500"><?= sanitize($u['name']) ?></div>
              <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($u['email']) ?></div>
            </div>
            <?php if ($u['role']==='admin'): ?>
              <span class="tag" style="background:rgba(242,169,61,0.15);color:var(--accent3);font-size:10px">Admin</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <a href="users.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;margin-top:12px">ดู Users ทั้งหมด →</a>
      </div>
    </div>

    <!-- Recent todos -->
    <div class="card" style="margin-top:16px">
      <div class="card-title">⏱ Todo ล่าสุดในระบบ</div>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($recentTodos as $t):
          $isDone = $t['status']==='done';
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;background:var(--glass);border-radius:var(--radius-sm);border-left:3px solid var(--<?= $t['priority']==='high'?'danger':($t['priority']==='medium'?'accent3':'accent2') ?>)">
          <span style="font-size:16px"><?= $isDone ? '✅' : '⏳' ?></span>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:500;<?= $isDone?'text-decoration:line-through;color:var(--text-muted)':'' ?>;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($t['title']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)">โดย <?= sanitize($t['user_name']) ?></div>
          </div>
          <div style="font-size:11px;color:var(--text-muted);white-space:nowrap"><?= date('d/m/y', strtotime($t['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <a href="todos.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;margin-top:12px">ดู Todo ทั้งหมด →</a>
    </div>

  </main>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="../assets/js/app.js"></script>
<script>
Chart.defaults.color = '#9ca3af';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
const C = { accent:'#b5f23d', green:'#7be0ad', orange:'#f2a93d', danger:'#f2503d', muted:'rgba(255,255,255,0.10)' };

// Daily line chart
new Chart(document.getElementById('chart-daily'), {
  type: 'line',
  data: {
    labels: <?= json_encode($dailyLabels) ?>,
    datasets: [{
      label: 'งานใหม่',
      data: <?= json_encode($dailyValues) ?>,
      borderColor: C.accent,
      backgroundColor: 'rgba(181,242,61,0.10)',
      borderWidth: 2,
      pointBackgroundColor: C.accent,
      pointRadius: 4,
      fill: true,
      tension: 0.4,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }
});

<?php if ($stats['total_todos'] > 0): ?>
new Chart(document.getElementById('chart-status'), {
  type: 'doughnut',
  data: {
    labels: ['เสร็จแล้ว','ยังไม่เสร็จ'],
    datasets: [{
      data: [<?= $stats['done_todos'] ?>, <?= $stats['pending_todos'] ?>],
      backgroundColor: [C.accent, C.muted],
      borderWidth: 0,
      hoverOffset: 6,
    }]
  },
  options: {
    cutout: '70%',
    plugins: { legend: { position:'right', labels:{ padding:16, font:{ family:'DM Sans', size:12 } } } }
  }
});
<?php endif; ?>
</script>
</body>
</html>
