<?php
require_once '../db/connection.php';
if (!isLoggedIn()) redirect('../auth/login.php');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membership_id = (int)($_POST['membership_id'] ?? 0);

    if ($membership_id <= 0) {
        $error = 'Lütfen bir plan seçin.';
    } else {
        $plan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM memberships WHERE membership_id=$membership_id"));
        if (!$plan) {
            $error = 'Geçersiz plan.';
        } else {
         
            mysqli_query($conn, "UPDATE payments SET status='expired' WHERE user_id=$user_id AND status='active'");

            $expiry = date('Y-m-d', strtotime('+' . $plan['duration_days'] . ' days'));
            $amount = $plan['price'];

            $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, membership_id, amount, expiry_date, status) VALUES (?,?,?,?,'active')");
            mysqli_stmt_bind_param($stmt, 'iids', $user_id, $membership_id, $amount, $expiry);

            if (mysqli_stmt_execute($stmt)) {
                $success = '✓ ' . $plan['name'] . ' başarıyla satın alındı! Bitiş tarihi: ' . date('d.m.Y', strtotime($expiry));
            } else {
                $error = 'İşlem sırasında hata oluştu.';
            }
        }
    }
}


$plans = [];
$r = mysqli_query($conn, "SELECT * FROM memberships ORDER BY price ASC");
while ($row = mysqli_fetch_assoc($r)) $plans[] = $row;

$history = mysqli_query($conn, "
    SELECT p.*, m.name AS plan_name
    FROM payments p
    JOIN memberships m ON p.membership_id = m.membership_id
    WHERE p.user_id=$user_id
    ORDER BY p.payment_date DESC
");

// Aktif üyelik
$active = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT p.*, m.name AS plan_name
    FROM payments p JOIN memberships m ON p.membership_id=m.membership_id
    WHERE p.user_id=$user_id AND p.status='active' AND p.expiry_date>=CURDATE()
    LIMIT 1
"));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Üyelik & Ödeme — PowerGym</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<nav>
  <a href="../index.php" class="nav-logo">POWER<span>GYM</span></a>
  <div class="nav-links">
    <a href="dashboard.php">Panelim</a>
    <a href="classes.php">Dersler</a>
    <a href="payment.php" class="active">Üyelik</a>
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
    <a href="classes.php">📅 Ders Programı</a>
    <a href="payment.php" class="active">💳 Üyelik & Ödeme</a>
    <div class="sidebar-section">Hesap</div>
    <a href="../auth/logout.php">🚪 Çıkış Yap</a>
  </aside>

  <main class="dash-content">
    <div class="page-header">
      <div class="page-title">ÜYELİK <span style="color:var(--primary);">&amp; ÖDEME</span></div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Mevcut Üyelik -->
    <?php if ($active): ?>
    <div class="card" style="border-color:var(--success);margin-bottom:2rem;">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
          <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:.3rem;">Aktif Üyeliğin</div>
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:1.5px;"><?= htmlspecialchars($active['plan_name']) ?></div>
          <div style="color:var(--text-muted);font-size:.88rem;">
            Bitiş tarihi: <strong style="color:var(--text);"><?= date('d.m.Y', strtotime($active['expiry_date'])) ?></strong>
          </div>
        </div>
        <span class="badge badge-success" style="font-size:.85rem;padding:.4rem 1rem;">AKTİF</span>
      </div>
    </div>
    <?php endif; ?>

    <!-- Planlar -->
    <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;letter-spacing:2px;margin-bottom:1.2rem;">
      <?= $active ? 'PLANI YENİLE / DEĞİŞTİR' : 'PLAN SEÇ' ?>
    </h2>

    <form method="POST">
      <div class="grid grid-3" style="margin-bottom:1.5rem;">
      <?php foreach ($plans as $plan):
        $popular = $plan['duration_days'] == 90;
      ?>
        <label style="cursor:pointer;">
          <input type="radio" name="membership_id" value="<?= $plan['membership_id'] ?>"
                 style="display:none;" class="plan-radio" id="plan-<?= $plan['membership_id'] ?>">
          <div class="card plan-card" id="card-<?= $plan['membership_id'] ?>"
               style="<?= $popular ? 'border-color:var(--primary);' : '' ?> transition:all .2s;"
               onclick="selectPlan(<?= $plan['membership_id'] ?>)">
            <?php if ($popular): ?>
              <span class="badge badge-primary" style="margin-bottom:.6rem;">En Popüler</span>
            <?php endif; ?>
            <h3><?= htmlspecialchars($plan['name']) ?></h3>
            <div style="font-family:'Bebas Neue',sans-serif;font-size:2.2rem;color:var(--primary);margin:.4rem 0;">
              ₺<?= number_format($plan['price'],0,'.','.') ?>
            </div>
            <p style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($plan['description']) ?></p>
          </div>
        </label>
      <?php endforeach; ?>
      </div>

      <button type="submit" class="btn btn-primary btn-lg">
        💳 Satın Al
      </button>
      <p style="font-size:.8rem;color:var(--text-muted);margin-top:.5rem;">
       
      </p>
    </form>

    <!-- Ödeme Geçmişi -->
    <div style="margin-top:3rem;">
      <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;letter-spacing:2px;margin-bottom:1rem;">
        ÖDEME GEÇMİŞİ
      </h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Plan</th>
              <th>Tutar</th>
              <th>Satın Alma</th>
              <th>Bitiş</th>
              <th>Durum</th>
            </tr>
          </thead>
          <tbody>
          <?php $i=0; while($p = mysqli_fetch_assoc($history)): $i++; ?>
            <tr>
              <td style="color:var(--text-muted);"><?= $i ?></td>
              <td><?= htmlspecialchars($p['plan_name']) ?></td>
              <td style="color:var(--primary);font-weight:700;">₺<?= number_format($p['amount'],2,',','.') ?></td>
              <td><?= date('d.m.Y', strtotime($p['payment_date'])) ?></td>
              <td><?= date('d.m.Y', strtotime($p['expiry_date'])) ?></td>
              <td>
                <?php if ($p['status']==='active' && $p['expiry_date'] >= date('Y-m-d')): ?>
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
            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem;">Henüz ödeme geçmişi yok.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<footer>
  <p>© 2026 <span>PowerGym</span> — CPE212 Proje, Grup 35</p>
</footer>

<script>
function selectPlan(id) {
  document.querySelectorAll('.plan-card').forEach(c => {
    c.style.outline = '';
    c.style.transform = '';
  });
  document.getElementById('plan-' + id).checked = true;
  const card = document.getElementById('card-' + id);
  card.style.outline = '2px solid var(--primary)';
  card.style.transform = 'translateY(-4px)';
}
</script>
</body>
</html>
