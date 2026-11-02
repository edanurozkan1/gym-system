<?php
require_once '../db/connection.php';
if (!isLoggedIn() || !isAdmin()) redirect('../auth/login.php');

// Filtreleme
$filter = sanitize($conn, $_GET['filter'] ?? 'all');
$where = '';
if ($filter === 'active')  $where = "WHERE p.status='active' AND p.expiry_date >= CURDATE()";
if ($filter === 'expired') $where = "WHERE p.status='expired' OR (p.status='active' AND p.expiry_date < CURDATE())";

$payments = mysqli_query($conn, "
    SELECT p.*, u.name AS user_name, u.email, m.name AS plan_name
    FROM payments p
    JOIN users u ON p.user_id=u.user_id
    JOIN memberships m ON p.membership_id=m.membership_id
    $where
    ORDER BY p.payment_date DESC
");

$total_revenue = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='active'"))[0];
$active_count  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM payments WHERE status='active' AND expiry_date>=CURDATE()"))[0];
$expired_count = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM payments WHERE status='expired' OR expiry_date<CURDATE()"))[0];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ödeme Yönetimi — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php">Admin</a>
    <a href="manage_users.php">Üyeler</a>
    <a href="manage_classes.php">Dersler</a>
    <a href="manage_payments.php" class="active">Ödemeler</a>
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
    <a href="manage_users.php">👥 Üye Yönetimi</a>
    <a href="manage_classes.php">📅 Ders Yönetimi</a>
    <a href="manage_payments.php" class="active">💰 Ödeme Yönetimi</a>
    <div class="sidebar-section">Sistem</div>
    <a href="../index.php">🌐 Siteye Git</a>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">ÖDEME <span style="color:var(--primary);">YÖNETİMİ</span></div>
    </div>

    
    <div class="stat-cards" style="margin-bottom:1.5rem;">
      <div class="stat-card">
        <div class="stat-card-icon">💰</div>
        <div>
          <div class="stat-card-num">₺<?= number_format($total_revenue,0,'.','.') ?></div>
          <div class="stat-card-lbl">Toplam Gelir</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">✅</div>
        <div>
          <div class="stat-card-num"><?= $active_count ?></div>
          <div class="stat-card-lbl">Aktif Üyelik</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">❌</div>
        <div>
          <div class="stat-card-num"><?= $expired_count ?></div>
          <div class="stat-card-lbl">Süresi Dolmuş</div>
        </div>
      </div>
    </div>

  
    <div style="display:flex;gap:.5rem;margin-bottom:1rem;">
      <a href="manage_payments.php?filter=all"     class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-ghost' ?>">Tümü</a>
      <a href="manage_payments.php?filter=active"  class="btn btn-sm <?= $filter==='active'?'btn-primary':'btn-ghost' ?>">Aktif</a>
      <a href="manage_payments.php?filter=expired" class="btn btn-sm <?= $filter==='expired'?'btn-primary':'btn-ghost' ?>">Süresi Dolmuş</a>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Üye</th>
            <th>E-Posta</th>
            <th>Plan</th>
            <th>Tutar</th>
            <th>Satın Alma</th>
            <th>Bitiş</th>
            <th>Durum</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=0; while($p = mysqli_fetch_assoc($payments)): $i++;
          $is_expired = $p['expiry_date'] < date('Y-m-d');
        ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $p['payment_id'] ?></td>
            <td><strong><?= htmlspecialchars($p['user_name']) ?></strong></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['plan_name']) ?></td>
            <td style="color:var(--primary);font-weight:700;">₺<?= number_format($p['amount'],2,',','.') ?></td>
            <td style="font-size:.82rem;"><?= date('d.m.Y', strtotime($p['payment_date'])) ?></td>
            <td style="font-size:.82rem;"><?= date('d.m.Y', strtotime($p['expiry_date'])) ?></td>
            <td>
              <?php if (!$is_expired && $p['status']==='active'): ?>
                <span class="badge badge-success">Aktif</span>
              <?php elseif ($p['status']==='cancelled'): ?>
                <span class="badge badge-danger">İptal</span>
              <?php else: ?>
                <span class="badge badge-muted">Süresi Doldu</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        <?php if ($i===0): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">Kayıt bulunamadı.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<footer>
  <p>© 2026 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>
</body>
</html>
