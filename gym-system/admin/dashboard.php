<?php
require_once '../db/connection.php';
if (!isLoggedIn() || !isAdmin()) redirect('../auth/login.php');

// İstatistikler
$total_members  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='member'"))[0];
$total_classes  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM classes"))[0];
$total_enroll   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM enrollments WHERE status='active'"))[0];
$total_revenue  = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='active'"))[0];
$active_members = mysqli_fetch_row(mysqli_query($conn, "
    SELECT COUNT(DISTINCT user_id) FROM payments WHERE status='active' AND expiry_date>=CURDATE()
"))[0];

$recent_users = mysqli_query($conn, "SELECT * FROM users WHERE role='member' ORDER BY created_at DESC LIMIT 5");

$class_capacity = mysqli_query($conn, "
    SELECT c.name, s.day_of_week, s.start_time, s.current_enrolled, c.max_capacity
    FROM schedule s JOIN classes c ON s.class_id=c.class_id
    ORDER BY s.current_enrolled DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php" class="active">Admin</a>
    <a href="manage_users.php">Üyeler</a>
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
    <a href="dashboard.php" class="active">📊 Dashboard</a>
    <a href="manage_users.php">👥 Üye Yönetimi</a>
    <a href="manage_classes.php">📅 Ders Yönetimi</a>
    <a href="manage_payments.php">💰 Ödeme Yönetimi</a>
    <div class="sidebar-section">Sistem</div>
    <a href="../index.php">🌐 Siteye Git</a>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">ADMİN <span style="color:var(--primary);">PANEL</span></div>
      <div style="font-size:.85rem;color:var(--text-muted);"><?= date('d.m.Y H:i') ?></div>
    </div>

    <!-- Stat Cards -->
    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-card-icon">👥</div>
        <div>
          <div class="stat-card-num"><?= $total_members ?></div>
          <div class="stat-card-lbl">Toplam Üye</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">✅</div>
        <div>
          <div class="stat-card-num"><?= $active_members ?></div>
          <div class="stat-card-lbl">Aktif Üyelik</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">📅</div>
        <div>
          <div class="stat-card-num"><?= $total_enroll ?></div>
          <div class="stat-card-lbl">Ders Kaydı</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">💰</div>
        <div>
          <div class="stat-card-num">₺<?= number_format($total_revenue,0,'.','.') ?></div>
          <div class="stat-card-lbl">Toplam Gelir</div>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
          <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;letter-spacing:1.5px;">SON KAYITLAR</h3>
          <a href="manage_users.php" class="btn btn-ghost btn-sm">Tümü →</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Ad</th><th>E-Posta</th><th>Tarih</th></tr></thead>
            <tbody>
            <?php while($u = mysqli_fetch_assoc($recent_users)): ?>
              <tr>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td style="font-size:.82rem;color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                <td style="font-size:.82rem;color:var(--text-muted);"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
          <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;letter-spacing:1.5px;">DERS DOLULUĞU</h3>
          <a href="manage_classes.php" class="btn btn-ghost btn-sm">Yönet →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:.8rem;">
        <?php while($cls = mysqli_fetch_assoc($class_capacity)):
          $pct = $cls['max_capacity'] > 0 ? ($cls['current_enrolled']/$cls['max_capacity'])*100 : 0;
          $fill = $pct>=100?'full':($pct>=75?'high':($pct>=50?'medium':''));
        ?>
          <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:.8rem 1rem;">
            <div style="display:flex;justify-content:space-between;margin-bottom:.4rem;">
              <span style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($cls['name']) ?> — <?= $cls['day_of_week'] ?></span>
              <span style="font-size:.82rem;color:var(--text-muted);"><?= $cls['current_enrolled'] ?>/<?= $cls['max_capacity'] ?></span>
            </div>
            <div class="capacity-bar">
              <div class="capacity-fill <?= $fill ?>" style="width:<?= min($pct,100) ?>%"></div>
            </div>
          </div>
        <?php endwhile; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<footer>
  <p>© 2026 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>
</body>
</html>
