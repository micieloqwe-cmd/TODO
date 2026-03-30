<?php
// ==========================================
// dashboard.php — Overview Dashboard
// ==========================================

require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

$user  = currentUser();
$uid   = (int)$user['id'];
$today = date('Y-m-d');
$csrf  = generateCsrfToken();

// Overall stats
$stmtStat = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status='done') AS done,
        SUM(status='pending') AS pending,
        SUM(status='pending' AND due_date IS NOT NULL AND due_date < :today) AS overdue,
        SUM(priority='high'   AND status='pending') AS high_pending,
        SUM(priority='medium' AND status='pending') AS medium_pending,
        SUM(priority='low'    AND status='pending') AS low_pending
    FROM todos WHERE user_id = :uid
");
$stmtStat->execute([':today' => $today, ':uid' => $uid]);
$stats = $stmtStat->fetch();

$pct = $stats['total'] > 0 ? round(($stats['done'] / $stats['total']) * 100) : 0;

// Priority breakdown
$stmtPri = $pdo->prepare("
    SELECT priority, COUNT(*) cnt, SUM(status='done') done
    FROM todos WHERE user_id=? GROUP BY priority
");
$stmtPri->execute([$uid]);
$priority = ['high'=>['cnt'=>0,'done'=>0],'medium'=>['cnt'=>0,'done'=>0],'low'=>['cnt'=>0,'done'=>0]];
foreach ($stmtPri->fetchAll() as $r) $priority[$r['priority']] = ['cnt'=>(int)$r['cnt'],'done'=>(int)$r['done']];

// Overdue items
$stmtOver = $pdo->prepare("
    SELECT * FROM todos
    WHERE user_id=? AND status='pending' AND due_date IS NOT NULL AND due_date < ?
    ORDER BY due_date ASC LIMIT 5
");
$stmtOver->execute([$uid, $today]);
$overdue = $stmtOver->fetchAll();

// Due today
$stmtToday = $pdo->prepare("SELECT * FROM todos WHERE user_id=? AND due_date=? AND status='pending' ORDER BY priority DESC LIMIT 5");
$stmtToday->execute([$uid, $today]);
$dueToday = $stmtToday->fetchAll();

// Recent activity (latest 5)
$stmtRecent = $pdo->prepare("SELECT * FROM todos WHERE user_id=? ORDER BY updated_at DESC LIMIT 5");
$stmtRecent->execute([$uid]);
$recent = $stmtRecent->fetchAll();

// --- Chart data for JS ---
$priorityChartData = [
    (int)$stats['total'] > 0 ? round($priority['high']['cnt']   / $stats['total'] * 100) : 0,
    (int)$stats['total'] > 0 ? round($priority['medium']['cnt'] / $stats['total'] * 100) : 0,
    (int)$stats['total'] > 0 ? round($priority['low']['cnt']    / $stats['total'] * 100) : 0,
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf" content="<?= $csrf ?>">
  <title>Dashboard — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>

<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">

    <div class="page-header">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">ภาพรวมงานของ <?= sanitize($user['name']) ?></p>
      </div>
      <a href="index.php" class="btn btn-primary">+ เพิ่มงานใหม่</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card" style="animation-delay:0s">
        <div class="stat-label">งานทั้งหมด</div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card" style="animation-delay:0.06s">
        <div class="stat-label">เสร็จแล้ว</div>
        <div class="stat-value green"><?= $stats['done'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card" style="animation-delay:0.12s">
        <div class="stat-label">ยังไม่เสร็จ</div>
        <div class="stat-value orange"><?= $stats['pending'] ?></div>
        <div class="stat-sub">รายการ</div>
      </div>
      <div class="stat-card" style="animation-delay:0.18s">
        <div class="stat-label">เกินกำหนด</div>
        <div class="stat-value red"><?= $stats['overdue'] ?></div>
        <div class="stat-sub">ต้องรีบจัดการ</div>
      </div>
    </div>

    <!-- Progress -->
    <div class="progress-wrap">
      <div class="progress-header">
        <span class="progress-label">Completion Rate โดยรวม</span>
        <span class="progress-pct"><?= $pct ?>%</span>
      </div>
      <div class="progress-bar-bg">
        <div class="progress-bar-fill" id="progress-fill" data-pct="<?= $pct ?>" style="width:0%"></div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="dashboard-grid">

      <!-- Donut: status -->
      <div class="card">
        <div class="card-title">สัดส่วนสถานะงาน</div>
        <?php if ($stats['total'] > 0): ?>
          <canvas id="chart-status" height="180"></canvas>
        <?php else: ?>
          <div class="empty-state" style="padding:20px">
            <div class="icon" style="font-size:32px">📋</div>
            <p>ยังไม่มีข้อมูล</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Donut: priority -->
      <div class="card">
        <div class="card-title">สัดส่วนตามความสำคัญ</div>
        <?php if ($stats['total'] > 0): ?>
          <canvas id="chart-priority" height="180"></canvas>
        <?php else: ?>
          <div class="empty-state" style="padding:20px">
            <div class="icon" style="font-size:32px">🎯</div>
            <p>ยังไม่มีข้อมูล</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Priority bar chart -->
      <div class="card" style="grid-column: 1 / -1">
        <div class="card-title">สถานะแยกตามความสำคัญ</div>
        <?php if ($stats['total'] > 0): ?>
          <canvas id="chart-bar" height="100"></canvas>
        <?php else: ?>
          <div class="empty-state" style="padding:20px"><p>ยังไม่มีข้อมูล</p></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Bottom row -->
    <div class="dashboard-grid" style="margin-top:16px">

      <!-- Overdue -->
      <div class="card">
        <div class="card-title">⚠ เกินกำหนด (<?= count($overdue) ?>)</div>
        <?php if (empty($overdue)): ?>
          <div style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0">
            🎉 ไม่มีงานที่เกินกำหนด
          </div>
        <?php else: ?>
          <div class="overdue-list">
            <?php foreach ($overdue as $t): ?>
              <div class="overdue-item">
                <span class="dot"></span>
                <span style="flex:1;font-size:13px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                  <?= sanitize($t['title']) ?>
                </span>
                <span class="overdue-date"><?= date('d/m/Y', strtotime($t['due_date'])) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Due today -->
      <div class="card">
        <div class="card-title">📅 กำหนดวันนี้ (<?= count($dueToday) ?>)</div>
        <?php if (empty($dueToday)): ?>
          <div style="color:var(--text-muted);font-size:13px;text-align:center;padding:20px 0">
            ✓ ไม่มีงานกำหนดวันนี้
          </div>
        <?php else: ?>
          <div class="overdue-list">
            <?php foreach ($dueToday as $t): ?>
              <div class="overdue-item" style="background:rgba(242,169,61,0.08);border-color:rgba(242,169,61,0.25)">
                <span class="dot" style="background:var(--accent3)"></span>
                <span style="flex:1;font-size:13px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                  <?= sanitize($t['title']) ?>
                </span>
                <span class="tag tag-priority-<?= $t['priority'] ?>" style="font-size:10px">
                  <?= $t['priority']==='high'?'สูง':($t['priority']==='medium'?'กลาง':'ต่ำ') ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="assets/js/app.js"></script>
<script>
// Chart color palette matching CSS vars
const C = {
  accent:  '#b5f23d',
  green:   '#7be0ad',
  orange:  '#f2a93d',
  danger:  '#f2503d',
  muted:   'rgba(255,255,255,0.12)',
  text:    '#e8eaf0',
  textDim: '#9ca3af',
};

Chart.defaults.color = C.textDim;
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

const doughnutOpts = {
  cutout: '70%',
  plugins: {
    legend: { position: 'right', labels: { padding: 16, font: { family: 'DM Sans', size: 12 } } }
  }
};

<?php if ($stats['total'] > 0): ?>
// Status chart
new Chart(document.getElementById('chart-status'), {
  type: 'doughnut',
  data: {
    labels: ['เสร็จแล้ว', 'ยังไม่เสร็จ'],
    datasets: [{
      data: [<?= (int)$stats['done'] ?>, <?= (int)$stats['pending'] ?>],
      backgroundColor: [C.accent, C.orange],
      borderWidth: 0,
      hoverOffset: 6,
    }]
  },
  options: { ...doughnutOpts }
});

// Priority chart
new Chart(document.getElementById('chart-priority'), {
  type: 'doughnut',
  data: {
    labels: ['สูง', 'กลาง', 'ต่ำ'],
    datasets: [{
      data: [<?= $priority['high']['cnt'] ?>, <?= $priority['medium']['cnt'] ?>, <?= $priority['low']['cnt'] ?>],
      backgroundColor: [C.danger, C.orange, C.green],
      borderWidth: 0,
      hoverOffset: 6,
    }]
  },
  options: { ...doughnutOpts }
});

// Bar chart
new Chart(document.getElementById('chart-bar'), {
  type: 'bar',
  data: {
    labels: ['🔴 สูง', '🟡 กลาง', '🟢 ต่ำ'],
    datasets: [
      {
        label: 'เสร็จแล้ว',
        data: [<?= $priority['high']['done'] ?>, <?= $priority['medium']['done'] ?>, <?= $priority['low']['done'] ?>],
        backgroundColor: C.accent,
        borderRadius: 6,
      },
      {
        label: 'ยังไม่เสร็จ',
        data: [
          <?= $priority['high']['cnt'] - $priority['high']['done'] ?>,
          <?= $priority['medium']['cnt'] - $priority['medium']['done'] ?>,
          <?= $priority['low']['cnt'] - $priority['low']['done'] ?>
        ],
        backgroundColor: C.muted,
        borderRadius: 6,
      }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top', labels: { padding: 16, font: { family: 'DM Sans', size: 12 } } } },
    scales: {
      x: { stacked: false, grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }
});
<?php endif; ?>
</script>
</body>
</html>
