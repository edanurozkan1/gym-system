<?php
require_once '../db/connection.php';

if (isLoggedIn()) redirect('../member/dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($conn, $_POST['name'] ?? '');
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $phone    = sanitize($conn, $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Ad, e-posta ve şifre alanları zorunludur.';
    } elseif (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } elseif ($password !== $confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else {
        
        $check = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email=?");
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = 'Bu e-posta adresi zaten kayıtlı.';
        } else {
            $hashed = hash('sha256', $password);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, phone, role) VALUES (?,?,?,?,'member')");
            mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $hashed, $phone);

            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name']    = $name;
                $_SESSION['role']    = 'member';
                redirect('../member/dashboard.php');
            } else {
                $error = 'Kayıt sırasında bir hata oluştu.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Üye Ol — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card fade-up">
    <div class="auth-logo">
      <div class="logo-text">POWERGYM</div>
      <div class="logo-sub">Üyelik & Ders Kayıt Sistemi</div>
    </div>

    <div class="auth-title">Üye Ol</div>
    <div class="auth-sub">Hemen başlamak için hesap oluştur</div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Ad Soyad</label>
        <input type="text" name="name" class="form-control"
               placeholder="Ahmet Yılmaz"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">E-Posta</label>
        <input type="email" name="email" class="form-control"
               placeholder="ornek@mail.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Telefon <span style="color:var(--text-muted);font-size:.75rem;">(isteğe bağlı)</span></label>
        <input type="tel" name="phone" class="form-control"
               placeholder="0532 123 45 67"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Şifre</label>
        <input type="password" name="password" class="form-control"
               placeholder="En az 6 karakter" required>
      </div>
      <div class="form-group">
        <label class="form-label">Şifre Tekrar</label>
        <input type="password" name="confirm" class="form-control"
               placeholder="Şifreyi tekrar gir" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.5rem;">
        Hesap Oluştur →
      </button>
    </form>

    <div class="auth-footer">
      Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
    </div>
  </div>
</div>
</body>
</html>
