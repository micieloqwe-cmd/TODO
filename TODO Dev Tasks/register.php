<?php
// ==========================================
// register.php
// ==========================================

require_once 'includes/config.php';
require_once 'includes/auth.php';

requireGuest();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';

        if (!$name || !$email || !$password || !$confirm) {
            $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $error = 'ชื่อต้องมีความยาว 2–100 ตัวอักษร';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'รูปแบบ Email ไม่ถูกต้อง';
        } elseif (strlen($password) < 8) {
            $error = 'Password ต้องมีอย่างน้อย 8 ตัวอักษร';
        } elseif ($password !== $confirm) {
            $error = 'Password ทั้งสองช่องไม่ตรงกัน';
        } else {
            // Check duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email นี้ถูกใช้งานแล้ว';
            } else {
                $hash = hashPassword($password);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)");
                $stmt->execute([$name, $email, $hash]);
                $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
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
  <title>สมัครสมาชิก — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
  <div class="auth-logo">Task<span>Flow</span></div>
  <p class="auth-subtitle">เริ่มต้นจัดการงานของคุณได้เลย</p>

  <h2>สมัครสมาชิก</h2>

  <?php if ($error):   ?><div class="alert alert-error">⚠ <?= sanitize($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success">✓ <?= sanitize($success) ?> <a href="login.php" style="color:var(--accent)">เข้าสู่ระบบ →</a></div><?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST" id="reg-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <div class="form-group">
      <label for="name">ชื่อ-นามสกุล</label>
      <input type="text" id="name" name="name"
             value="<?= sanitize($_POST['name'] ?? '') ?>"
             placeholder="สมชาย ใจดี" autocomplete="name" required>
      <div class="form-error" id="err-name">กรุณากรอกชื่ออย่างน้อย 2 ตัวอักษร</div>
    </div>

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
             placeholder="อย่างน้อย 8 ตัวอักษร" autocomplete="new-password" required>
      <div class="form-error" id="err-pass">Password ต้องมีอย่างน้อย 8 ตัวอักษร</div>
    </div>

    <div class="form-group">
      <label for="confirm">ยืนยัน Password</label>
      <input type="password" id="confirm" name="confirm"
             placeholder="พิมพ์ Password อีกครั้ง" autocomplete="new-password" required>
      <div class="form-error" id="err-confirm">Password ไม่ตรงกัน</div>
    </div>

    <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px">
      สมัครสมาชิก
    </button>
  </form>
  <?php endif; ?>

  <p style="text-align:center; margin-top:24px; font-size:14px; color:var(--text-muted)">
    มีบัญชีแล้ว? <a href="login.php" style="color:var(--accent); text-decoration:none; font-weight:500">เข้าสู่ระบบ</a>
  </p>
</div>

<script>
document.getElementById('reg-form')?.addEventListener('submit', function(e) {
  let ok = true;
  const name    = document.getElementById('name');
  const email   = document.getElementById('email');
  const pass    = document.getElementById('password');
  const confirm = document.getElementById('confirm');

  const show = (id) => { document.getElementById(id).classList.add('show'); ok = false; };
  const hide = (id) => document.getElementById(id).classList.remove('show');

  name.value.trim().length >= 2 ? hide('err-name') : show('err-name');
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) ? hide('err-email') : show('err-email');
  pass.value.length >= 8 ? hide('err-pass') : show('err-pass');
  pass.value === confirm.value && confirm.value ? hide('err-confirm') : show('err-confirm');

  if (!ok) e.preventDefault();
});
</script>
</body>
</html>
