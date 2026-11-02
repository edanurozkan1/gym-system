<?php
require_once '../db/connection.php';
if (!isLoggedIn()) redirect('../auth/login.php');

$user_id = $_SESSION['user_id'];
$has_membership = hasActiveMembership($conn, $user_id);

$enrolled_ids = [];
$er = mysqli_query($conn, "SELECT schedule_id FROM enrollments WHERE user_id=$user_id AND status='active'");
while ($row = mysqli_fetch_assoc($er)) $enrolled_ids[] = $row['schedule_id'];


$schedule = mysqli_query($conn, "
    SELECT s.schedule_id, s.day_of_week, s.start_time, s.end_time, s.room,
           s.current_enrolled, c.max_capacity,
           c.name AS class_name, c.description, c.duration,
           u.name AS trainer_name
    FROM schedule s
    JOIN classes c ON s.class_id = c.class_id
    JOIN trainers t ON c.trainer_id = t.trainer_id
    JOIN users u ON t.user_id = u.user_id
    ORDER BY FIELD(s.day_of_week,'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'), s.start_time
");


$by_day = [];
while ($row = mysqli_fetch_assoc($schedule)) {
    $by_day[$row['day_of_week']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ders Programı — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php">Panelim</a>
    <a href="classes.php" class="active">Dersler</a>
    <a href="payment.php">Üyelik</a>
    <a href="../auth/logout.php" class="btn-nav">Çıkış</a>
  </div>
</nav>

<div class="dash-wrap">
  <aside class="sidebar">
    <div style="padding:.5rem;margin-bottom:1rem;">
      <div style="font-size:.75rem;color:var(--text-muted);">HOŞGELDİN</div>
      <div style="font-weight:700;"><?= htmlspecialchars($_SESSION['name']) ?></div>
    </div>
    <div class="sidebar-section">Menü</div>
    <a href="dashboard.php">🏠 Anasayfa</a>
    <a href="classes.php" class="active">📅 Ders Programı</a>
    <a href="payment.php">💳 Üyelik & Ödeme</a>
    <div class="sidebar-section">Hesap</div>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">DERS <span style="color:var(--primary);">PROGRAMI</span></div>
      <div style="font-size:.85rem;color:var(--text-muted);">
        Kapasite anlık olarak güncellenir
        <span class="spinner" style="margin-left:.5rem;"></span>
      </div>
    </div>

    <?php if (!$has_membership): ?>
      <div class="alert alert-warning">
        ⚠️ Derse kayıt olmak için aktif üyeliğin olmalı.
        <a href="payment.php" style="color:inherit;text-decoration:underline;margin-left:.3rem;">Üyelik Al →</a>
      </div>
    <?php endif; ?>

    <?php foreach ($by_day as $day => $sessions): ?>
    <div style="margin-bottom:2.5rem;">
      <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:2px;
                 border-left:4px solid var(--primary);padding-left:.8rem;margin-bottom:1rem;">
        <?= $day ?>
      </h2>
      <div class="grid grid-2">
      <?php foreach ($sessions as $cls):
        $pct = $cls['max_capacity'] > 0 ? ($cls['current_enrolled'] / $cls['max_capacity']) * 100 : 0;
        $fill_class = $pct >= 100 ? 'full' : ($pct >= 75 ? 'high' : ($pct >= 50 ? 'medium' : ''));
        $is_enrolled = in_array($cls['schedule_id'], $enrolled_ids);
        $is_full = $pct >= 100;
      ?>
      <div class="class-card fade-up">
        <div class="class-card-header">
          <h3><?= htmlspecialchars($cls['class_name']) ?></h3>
          <?php if ($is_enrolled): ?>
            <span class="badge badge-success">Kayıtlısın</span>
          <?php elseif ($is_full): ?>
            <span class="badge badge-danger">Dolu</span>
          <?php endif; ?>
        </div>
        <div class="class-card-body">
          <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:.8rem;">
            <?= htmlspecialchars($cls['description']) ?>
          </p>
          <div class="class-meta">
            <span class="meta-tag">🕐 <?= substr($cls['start_time'],0,5) ?> - <?= substr($cls['end_time'],0,5) ?></span>
            <span class="meta-tag">⏱ <?= $cls['duration'] ?> dk</span>
            <span class="meta-tag">📍 <?= htmlspecialchars($cls['room']) ?></span>
            <span class="meta-tag">👤 <?= htmlspecialchars($cls['trainer_name']) ?></span>
          </div>

          <div class="capacity-wrap" id="cap-<?= $cls['schedule_id'] ?>" data-schedule="<?= $cls['schedule_id'] ?>">
            <div class="capacity-label">
              <span>Kapasite</span>
              <span class="cap-text"><?= $cls['current_enrolled'] ?> / <?= $cls['max_capacity'] ?></span>
            </div>
            <div class="capacity-bar">
              <div class="capacity-fill <?= $fill_class ?>" style="width:<?= min($pct,100) ?>%"></div>
            </div>
          </div>

          <?php if ($is_enrolled): ?>
            <button class="btn btn-danger btn-sm"
              id="cancel-btn-<?= $cls['schedule_id'] ?>"
              onclick="cancelEnroll(<?= $cls['schedule_id'] ?>, this)">
              ✗ Kaydı İptal Et
            </button>
          <?php elseif ($is_full): ?>
            <button class="btn btn-ghost btn-sm" disabled>Kontenjan Dolu</button>
          <?php elseif (!$has_membership): ?>
            <button class="btn btn-outline btn-sm" disabled>Üyelik Gerekli</button>
          <?php else: ?>
            <button class="btn btn-primary btn-sm"
              id="enroll-btn-<?= $cls['schedule_id'] ?>"
              onclick="enrollClass(<?= $cls['schedule_id'] ?>, this)">
              + Kayıt Ol
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($by_day)): ?>
      <div class="alert alert-info">Henüz ders programı eklenmemiş.</div>
    <?php endif; ?>
  </main>
</div>

<footer>
  <p>© 2025 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>

<script src="../js/ajax.js"></script>
</body>
</html>
