<?php
require_once '../db/connection.php';
if (!isLoggedIn() || !isAdmin()) redirect('../auth/login.php');

$success = $error = '';

// Ders sil
if (isset($_GET['delete_schedule'])) {
    $id = (int)$_GET['delete_schedule'];
    mysqli_query($conn, "DELETE FROM enrollments WHERE schedule_id=$id");
    mysqli_query($conn, "DELETE FROM schedule WHERE schedule_id=$id");
    $success = 'Ders programdan silindi.';
}

// Yeni ders programı ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $class_id   = (int)$_POST['class_id'];
    $day        = sanitize($conn, $_POST['day_of_week']);
    $start      = sanitize($conn, $_POST['start_time']);
    $end        = sanitize($conn, $_POST['end_time']);
    $room       = sanitize($conn, $_POST['room']);

    if ($class_id && $day && $start && $end && $room) {
        mysqli_query($conn, "INSERT INTO schedule (class_id, day_of_week, start_time, end_time, room)
                             VALUES ($class_id,'$day','$start','$end','$room')");
        $success = 'Yeni ders programına eklendi.';
    } else {
        $error = 'Tüm alanları doldurun.';
    }
}


$classes_list = mysqli_query($conn, "SELECT * FROM classes");


$schedule_list = mysqli_query($conn, "
    SELECT s.*, c.name AS class_name, c.max_capacity, u.name AS trainer_name
    FROM schedule s
    JOIN classes c ON s.class_id=c.class_id
    JOIN trainers t ON c.trainer_id=t.trainer_id
    JOIN users u ON t.user_id=u.user_id
    ORDER BY FIELD(s.day_of_week,'Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'), s.start_time
");
$days = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi','Pazar'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ders Yönetimi — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php">Admin</a>
    <a href="manage_users.php">Üyeler</a>
    <a href="manage_classes.php" class="active">Dersler</a>
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
    <a href="manage_users.php">👥 Üye Yönetimi</a>
    <a href="manage_classes.php" class="active">📅 Ders Yönetimi</a>
    <a href="manage_payments.php">💰 Ödeme Yönetimi</a>
    <div class="sidebar-section">Sistem</div>
    <a href="../index.php">🌐 Siteye Git</a>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">DERS <span style="color:var(--primary);">YÖNETİMİ</span></div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Yeni Ders Ekle -->
    <div class="card" style="margin-bottom:2rem;">
      <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:1.5px;margin-bottom:1rem;">
        + PROGRAMA DERS EKLE
      </h3>
      <form method="POST">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:1rem;">
          <div class="form-group" style="margin:0;">
            <label class="form-label">Ders</label>
            <select name="class_id" class="form-control" required>
              <option value="">Seç...</option>
              <?php $cl2 = mysqli_query($conn,"SELECT * FROM classes");
                    while($c=mysqli_fetch_assoc($cl2)): ?>
                <option value="<?=$c['class_id']?>"><?=htmlspecialchars($c['name'])?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Gün</label>
            <select name="day_of_week" class="form-control" required>
              <?php foreach($days as $d): ?><option><?=$d?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Başlangıç</label>
            <input type="time" name="start_time" class="form-control" required>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Bitiş</label>
            <input type="time" name="end_time" class="form-control" required>
          </div>
          <div class="form-group" style="margin:0;">
            <label class="form-label">Salon</label>
            <input type="text" name="room" class="form-control" placeholder="Salon A" required>
          </div>
        </div>
        <button type="submit" name="add_schedule" class="btn btn-primary" style="margin-top:1rem;">
          Ekle
        </button>
      </form>
    </div>

  
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Ders</th>
            <th>Antrenör</th>
            <th>Gün</th>
            <th>Saat</th>
            <th>Salon</th>
            <th>Doluluk</th>
            <th>İşlem</th>
          </tr>
        </thead>
        <tbody>
        <?php while($s = mysqli_fetch_assoc($schedule_list)):
          $pct = $s['max_capacity'] > 0 ? round(($s['current_enrolled']/$s['max_capacity'])*100) : 0;
        ?>
          <tr>
            <td><strong><?= htmlspecialchars($s['class_name']) ?></strong></td>
            <td><?= htmlspecialchars($s['trainer_name']) ?></td>
            <td><?= $s['day_of_week'] ?></td>
            <td><?= substr($s['start_time'],0,5) ?> - <?= substr($s['end_time'],0,5) ?></td>
            <td><?= htmlspecialchars($s['room']) ?></td>
            <td>
              <span class="badge <?= $pct>=100?'badge-danger':($pct>=75?'badge-warning':'badge-success') ?>">
                <?= $s['current_enrolled'] ?>/<?= $s['max_capacity'] ?> (%<?= $pct ?>)
              </span>
            </td>
            <td>
              <a href="manage_classes.php?delete_schedule=<?= $s['schedule_id'] ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('Bu dersi silmek istediğinizden emin misiniz? Tüm kayıtlar da silinecek.')">
                Sil
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
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
