<?php
require_once '../db/connection.php';
if (!isLoggedIn()) redirect('../auth/login.php');

$user_id = $_SESSION['user_id'];


$user_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id=$user_id"));


$membership = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, m.name AS plan_name, m.duration_days
    FROM payments p
    JOIN memberships m ON p.membership_id = m.membership_id
    WHERE p.user_id=$user_id AND p.status='active' AND p.expiry_date >= CURDATE()
    ORDER BY p.expiry_date DESC LIMIT 1
"));

$enrollments = mysqli_query($conn, "
    SELECT e.enrollment_id, e.enrolled_at, e.schedule_id,
           c.name AS class_name, c.duration,
           s.day_of_week, s.start_time, s.end_time, s.room,
           s.current_enrolled, c.max_capacity,
           u.name AS trainer_name
    FROM enrollments e
    JOIN schedule s ON e.schedule_id = s.schedule_id
    JOIN classes c ON s.class_id = c.class_id
    JOIN trainers t ON c.trainer_id = t.trainer_id
    JOIN users u ON t.user_id = u.user_id
    WHERE e.user_id=$user_id AND e.status='active'
    ORDER BY FIELD(s.day_of_week,'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'), s.start_time
");

$enroll_count = mysqli_num_rows($enrollments);


$total_paid = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE user_id=$user_id"))[0];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panelim — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php" class="active">Panelim</a>
    <a href="classes.php">Dersler</a>
    <a href="payment.php">Üyelik</a>
    <a href="../auth/logout.php" class="btn-nav">Çıkış</a>
  </div>
</nav>

<div class="dash-wrap">
  <aside class="sidebar">
    <div style="padding:.5rem;margin-bottom:1rem;">
      <div style="font-size:.75rem;color:var(--text-muted);">HOŞGELDİN</div>
      <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($_SESSION['name']) ?></div>
    </div>
    <div class="sidebar-section">Menü</div>
    <a href="dashboard.php" class="active">🏠 Anasayfa</a>
    <a href="classes.php">📅 Ders Programı</a>
    <a href="payment.php">💳 Üyelik & Ödeme</a>
    <div class="sidebar-section">Hesap</div>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">PAN<span style="color:var(--primary);">ELİM</span></div>
      <a href="classes.php" class="btn btn-primary btn-sm">+ Derse Kayıt Ol</a>
    </div>

    <div class="stat-cards">
      <div class="stat-card">
        <div class="stat-card-icon">📅</div>
        <div>
          <div class="stat-card-num"><?= $enroll_count ?></div>
          <div class="stat-card-lbl">Kayıtlı Ders</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">💳</div>
        <div>
          <div class="stat-card-num"><?= $membership ? '✓' : '✗' ?></div>
          <div class="stat-card-lbl">Üyelik Durumu</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon">💰</div>
        <div>
          <div class="stat-card-num">₺<?= number_format($total_paid,0,'.','.') ?></div>
          <div class="stat-card-lbl">Toplam Ödeme</div>
        </div>
      </div>
    </div>

    <?php if ($membership): ?>
      <div class="alert alert-success" style="margin-bottom:1.5rem;">
        ✓ <strong><?= htmlspecialchars($membership['plan_name']) ?></strong> — 
        Bitiş: <strong><?= date('d.m.Y', strtotime($membership['expiry_date'])) ?></strong>
        &nbsp;<a href="payment.php" style="color:inherit;text-decoration:underline;">Yenile</a>
      </div>
    <?php else: ?>
      <div class="alert alert-warning" style="margin-bottom:1.5rem;">
        ⚠️ Aktif üyeliğin yok. <a href="payment.php" style="color:inherit;text-decoration:underline;">Üyelik satın al →</a>
      </div>
    <?php endif; ?>

    
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1.5px;">
        KAYITLI DERSLERİM
      </h2>
      <a href="classes.php" class="btn btn-ghost btn-sm">Tüm Dersler →</a>
    </div>

    <?php if ($enroll_count === 0): ?>
      <div class="alert alert-info">Henüz hiçbir derse kayıt olmadın. <a href="classes.php" style="color:inherit;text-decoration:underline;">Derslere göz at →</a></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Ders</th>
            <th>Gün</th>
            <th>Saat</th>
            <th>Salon</th>
            <th>Antrenör</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($e = mysqli_fetch_assoc($enrollments)): ?>
          <tr id="enrollment-row-<?= $e['schedule_id'] ?>">
            <td><strong><?= htmlspecialchars($e['class_name']) ?></strong><br>
              <span style="font-size:.78rem;color:var(--text-muted);"><?= $e['duration'] ?> dk</span>
            </td>
            <td><?= $e['day_of_week'] ?></td>
            <td><?= substr($e['start_time'],0,5) ?> - <?= substr($e['end_time'],0,5) ?></td>
            <td><?= htmlspecialchars($e['room']) ?></td>
            <td><?= htmlspecialchars($e['trainer_name']) ?></td>
            <td>
              <button class="btn btn-danger btn-sm"
                id="cancel-btn-<?= $e['schedule_id'] ?>"
                onclick="cancelEnroll(<?= $e['schedule_id'] ?>, this)">
                İptal
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>

<footer>
  <p>© 2026 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>

<script src="../js/ajax.js"></script>
</body>
</html>
