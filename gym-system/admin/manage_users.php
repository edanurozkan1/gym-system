<?php
require_once '../db/connection.php';
if (!isLoggedIn() || !isAdmin()) redirect('../auth/login.php');

$success = $error = '';

// Üye sil
if (isset($_GET['delete']) && (int)$_GET['delete'] > 1) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id=$del_id AND role!='admin'");
    $success = 'Kullanıcı silindi.';
}

// Tüm üyeler
$users = mysqli_query($conn, "
    SELECT u.*, 
           (SELECT COUNT(*) FROM enrollments e WHERE e.user_id=u.user_id AND e.status='active') AS enroll_count,
           (SELECT p.expiry_date FROM payments p WHERE p.user_id=u.user_id AND p.status='active' AND p.expiry_date>=CURDATE() ORDER BY p.expiry_date DESC LIMIT 1) AS active_until
    FROM users u
    ORDER BY u.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Üye Yönetimi — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php">Admin</a>
    <a href="manage_users.php" class="active">Üyeler</a>
    <a href="manage_classes.php">Dersler</a>
    <a href="manage_payments.php">Ödemeler</a>
    <a href="../auth/logout.php" class="btn-nav">Çıkış</a>
  </div>
</nav>

<div class="dash-wrap">
  <aside class="sidebar">
    <div style="padding:.5rem;margin-bottom:1rem;">
      <span class="badge badge-primary">ADMİN</span>
      <div style="font-weight:700;margin-top:.4rem;"><?= htmlspecialchars($_SESSION['name']) ?></div>
    </div>
    <div class="sidebar-section">Yönetim</div>
    <a href="dashboard.php">📊 Dashboard</a>
    <a href="manage_users.php" class="active">👥 Üye Yönetimi</a>
    <a href="manage_classes.php">📅 Ders Yönetimi</a>
    <a href="manage_payments.php">💰 Ödeme Yönetimi</a>
    <div class="sidebar-section">Sistem</div>
    <a href="../index.php">🌐 Siteye Git</a>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">ÜYE <span style="color:var(--primary);">YÖNETİMİ</span></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Ad Soyad</th>
            <th>E-Posta</th>
            <th>Telefon</th>
            <th>Rol</th>
            <th>Ders Kaydı</th>
            <th>Üyelik Bitiş</th>
            <th>Kayıt Tarihi</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
        <?php while($u = mysqli_fetch_assoc($users)): ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $u['user_id'] ?></td>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
            <td>
              <?php if ($u['role']==='admin'): ?>
                <span class="badge badge-primary">Admin</span>
              <?php elseif ($u['role']==='trainer'): ?>
                <span class="badge badge-warning">Antrenör</span>
              <?php else: ?>
                <span class="badge badge-muted">Üye</span>
              <?php endif; ?>
            </td>
            <td><?= $u['enroll_count'] ?></td>
            <td>
              <?php if ($u['active_until']): ?>
                <span class="badge badge-success"><?= date('d.m.Y', strtotime($u['active_until'])) ?></span>
              <?php else: ?>
                <span class="badge badge-danger">Yok</span>
              <?php endif; ?>
            </td>
            <td style="font-size:.82rem;"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if ($u['role'] !== 'admin'): ?>
                <a href="manage_users.php?delete=<?= $u['user_id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">
                  Sil
                </a>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:.8rem;">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<footer>
  <p>© 2025 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>
</body>
</html>
