<?php
// ==========================================
// login.php
// ==========================================

require_once 'includes/config.php';
require_once 'includes/auth.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $error = 'กรุณากรอก Email และ Password';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'รูปแบบ Email ไม่ถูกต้อง';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['name'];
                $_SESSION['user_role']     = $user['role'];
                $_SESSION['last_activity'] = time();
                header('Location: index.php');
                exit;
            } else {
                $error = 'Email หรือ Password ไม่ถูกต้อง';
            }
        }
    }
}

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
  <div class="auth-logo">Task<span>Flow</span></div>
  <p class="auth-subtitle">จัดการงานของคุณอย่างมีประสิทธิภาพ</p>
  
  <h2>เข้าสู่ระบบ</h2>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= sanitize($error) ?></div>
  <?php endif; ?>

  <form method="POST" id="login-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <div class="form-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"
             value="<?= sanitize($_POST['email'] ?? '') ?>"
             placeholder="you@example.com" autocomplete="email" required>
      <div class="form-error" id="err-email">กรุณากรอก Email ที่ถูกต้อง</div>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password"
             placeholder="••••••••" autocomplete="current-password" required>
      <div class="form-error" id="err-password">กรุณากรอก Password</div>
    </div>

    <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px">
      เข้าสู่ระบบ
    </button>
  </form>

  <p style="text-align:center; margin-top:24px; font-size:14px; color:var(--text-muted)">
    ยังไม่มีบัญชี? <a href="register.php" style="color:var(--accent); text-decoration:none; font-weight:500">สมัครสมาชิก</a>
  </p>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', function(e) {
  let ok = true;
  const email = document.getElementById('email');
  const pass  = document.getElementById('password');

  if (!email.value.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    document.getElementById('err-email').classList.add('show'); ok = false;
  } else {
    document.getElementById('err-email').classList.remove('show');
  }

  if (!pass.value) {
    document.getElementById('err-password').classList.add('show'); ok = false;
  } else {
    document.getElementById('err-password').classList.remove('show');
  }

  if (!ok) e.preventDefault();
});
</script>
</body>
</html>
