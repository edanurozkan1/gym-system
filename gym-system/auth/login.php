<?php
require_once '../db/connection.php';


if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../member/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'E-posta ve şifre alanları boş bırakılamaz.';
    } else {
        $hashed = hash('sha256', $password);
        $stmt = mysqli_prepare($conn, "SELECT user_id, name, role FROM users WHERE email=? AND password=?");
        mysqli_stmt_bind_param($stmt, 'ss', $email, $hashed);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') redirect('../admin/dashboard.php');
            else redirect('../member/dashboard.php');
        } else {
            $error = 'E-posta veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giriş Yap — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card fade-up">
    <div class="auth-logo">
      <div class="logo-text">POWERGYM</div>
      <div class="logo-sub">Üyelik & Ders Kayıt Sistemi</div>
    </div>

    <div class="auth-title">Giriş Yap</div>
    <div class="auth-sub">Hesabınla devam et</div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">E-Posta</label>
        <input type="email" name="email" class="form-control"
               placeholder="ornek@mail.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.5rem;">
       
      </button>
    </form>

    <div class="auth-footer">
      Hesabın yok mu? <a href="register.php">Üye Ol</a>
    </div>

    <div style="margin-top:1.5rem;padding:1rem;background:var(--bg3);border-radius:var(--radius);font-size:.8rem;color:var(--text-muted);">
      <strong style="color:var(--primary);">Test Hesapları:</strong><br>
      Admin: admin@gym.com / admin123<br>
      Üye: ahmet@mail.com / 123456
    </div>
  </div>
</div>
</body>
</html>
