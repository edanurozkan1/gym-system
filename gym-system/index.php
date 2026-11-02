<?php
require_once 'db/connection.php';


$schedule_result = mysqli_query($conn, "
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

$classes = [];
while ($row = mysqli_fetch_assoc($schedule_result)) {
    $classes[] = $row;
}


$total_members = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users WHERE role='member'"))[0];
$total_classes = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM classes"))[0];
$total_trainers = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM trainers"))[0];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PowerGym — Spor Salonu Sistemi</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>


<nav>
  <a href="index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <?php if (isLoggedIn()): ?>
      <a href="member/dashboard.php">Dashboard</a>
      <a href="member/classes.php">Dersler</a>
      <?php if (isAdmin()): ?>
        <a href="admin/dashboard.php">Admin Panel</a>
      <?php endif; ?>
      <a href="auth/logout.php" class="btn-nav">Çıkış Yap</a>
    <?php else: ?>
      <a href="auth/login.php">Giriş Yap</a>
      <a href="auth/register.php" class="btn-nav">Üye Ol</a>
    <?php endif; ?>
  </div>
</nav>


<section class="hero">
  <div class="hero-badge">CPE212 Proje — Grup 35</div>
  <h1 class="fade-up">GÜÇLÜ<br><span>BAŞLA.</span></h1>
  <p class="fade-up delay-1">Spor salonuna üye ol, ders programına kayıt ol ve antrenmanlarını takip et.</p>
  <div class="hero-btns fade-up delay-2">
    <?php if (!isLoggedIn()): ?>
      <a href="auth/register.php" class="btn btn-primary btn-lg">Hemen Üye Ol</a>
      <a href="auth/login.php" class="btn btn-outline btn-lg">Giriş Yap</a>
    <?php else: ?>
      <a href="member/classes.php" class="btn btn-primary btn-lg">Derslere Bak</a>
      <a href="member/dashboard.php" class="btn btn-outline btn-lg">Panelim</a>
    <?php endif; ?>
  </div>
</section>

<div class="stats-bar">
  <div class="stat-item">
    <div class="num"><?= $total_members ?>+</div>
    <div class="lbl">Aktif Üye</div>
  </div>
  <div class="stat-item">
    <div class="num"><?= $total_classes ?></div>
    <div class="lbl">Ders Türü</div>
  </div>
  <div class="stat-item">
    <div class="num"><?= $total_trainers ?></div>
    <div class="lbl">Antrenör</div>
  </div>
  <div class="stat-item">
    <div class="num">7</div>
    <div class="lbl">Gün Açık</div>
  </div>
</div>

<section class="section">
  <div class="container">
    <p class="section-title fade-up">NEDEN <span>POWERGYM?</span></p>
    <p class="section-sub">Modern spor salonu yönetim sistemi</p>
    <div class="grid grid-3">
      <div class="card fade-up delay-1">
        <span class="card-icon">🏋️</span>
        <h3>Ders Programı</h3>
        <p>Haftalık ders programını görüntüle, dilediğin derse kayıt ol veya iptal et.</p>
      </div>
      <div class="card fade-up delay-2">
        <span class="card-icon">⚡</span>
        <h3>Anlık Kapasite</h3>
        <p>AJAX teknolojisi ile ders kapasitesi anlık güncellenir, boş yer gösterilir.</p>
      </div>
      <div class="card fade-up delay-3">
        <span class="card-icon">💳</span>
        <h3>Üyelik Yönetimi</h3>
        <p>Aylık, 3 aylık veya yıllık üyelik planlarından birini seç, kolayca yenile.</p>
      </div>
    </div>
  </div>
</section>


<section class="section" style="background: var(--bg2); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
  <div class="container">
    <p class="section-title">DERS <span>PROGRAMI</span></p>
    <p class="section-sub">Bu haftaki derslerimize göz at</p>

    <?php if (empty($classes)): ?>
      <div class="alert alert-info">Henüz ders programı eklenmemiş.</div>
    <?php else: ?>
    <div class="grid grid-2">
      <?php foreach ($classes as $cls):
        $pct = $cls['max_capacity'] > 0 ? ($cls['current_enrolled'] / $cls['max_capacity']) * 100 : 0;
        $fill_class = $pct >= 100 ? 'full' : ($pct >= 75 ? 'high' : ($pct >= 50 ? 'medium' : ''));
        $remaining = $cls['max_capacity'] - $cls['current_enrolled'];
      ?>
      <div class="class-card fade-up">
        <div class="class-card-header">
          <h3><?= htmlspecialchars($cls['class_name']) ?></h3>
          <span style="font-size:.8rem;font-weight:600;color:rgba(255,255,255,.85);">
            <?= $cls['day_of_week'] ?>
          </span>
        </div>
        <div class="class-card-body">
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

          <?php if (isLoggedIn()): ?>
            <?php if ($pct >= 100): ?>
              <button class="btn btn-ghost btn-sm" disabled>Dolu</button>
            <?php else: ?>
              <a href="member/classes.php" class="btn btn-primary btn-sm">Kayıt Ol →</a>
            <?php endif; ?>
          <?php else: ?>
            <a href="auth/login.php" class="btn btn-outline btn-sm">Giriş Yaparak Kayıt Ol</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>


<section class="section">
  <div class="container">
    <p class="section-title">ÜYELİK <span>PLANLARI</span></p>
    <p class="section-sub">Sana uygun planı seç</p>
    <?php
    $plans = mysqli_query($conn, "SELECT * FROM memberships ORDER BY price ASC");
    ?>
    <div class="grid grid-3">
    <?php while($plan = mysqli_fetch_assoc($plans)):
      $highlight = $plan['duration_days'] == 90;
    ?>
      <div class="card" style="<?= $highlight ? 'border-color:var(--primary);' : '' ?>">
        <?php if($highlight): ?>
          <span class="badge badge-primary" style="margin-bottom:.8rem;">En Popüler</span>
        <?php endif; ?>
        <h3><?= htmlspecialchars($plan['name']) ?></h3>
        <div style="font-family:'Bebas Neue',sans-serif;font-size:2.5rem;color:var(--primary);margin:.5rem 0;">
          ₺<?= number_format($plan['price'],0,'.','.') ?>
        </div>
        <p style="margin-bottom:1rem;"><?= htmlspecialchars($plan['description']) ?></p>
        <a href="<?= isLoggedIn() ? 'member/payment.php?plan='.$plan['membership_id'] : 'auth/register.php' ?>" class="btn <?= $highlight ? 'btn-primary' : 'btn-outline' ?> btn-sm">
          Seç
        </a>
      </div>
    <?php endwhile; ?>
    </div>
  </div>
</section>

<footer>
  <p>© 2026 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>

<script src="js/ajax.js"></script>
</body>
</html>
