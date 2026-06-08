<?php
require_once 'includes/config.php';
startSession();
$active = 'kamar';
$user   = getLoggedInUser();

$db    = getDB();
$rooms = $db->query('SELECT * FROM rooms ORDER BY nomor ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Kamar - <?= SITE_NAME ?></title>
  <link rel="icon" type="image/png" href="assets/images/logo-apik.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container section">
  <h2>Daftar Kamar</h2>
  <p class="text-muted">Pilih kamar yang tersedia dan lakukan booking online dengan mudah.</p>

  <?php if ($user): ?>
    <div class="user-info-box">
      Login sebagai: <strong><?= e($user['name']) ?></strong>
      &nbsp;|&nbsp; Role: <strong><?= e($user['role']) ?></strong>
    </div>
  <?php else: ?>
    <div class="user-info-box">
      Anda belum login.
      Silakan <a href="login.php" style="color:var(--purple);font-weight:600;">login</a>
      untuk booking kamar.
    </div>
  <?php endif; ?>

  <div class="room-grid">
    <?php foreach ($rooms as $room): ?>
      <?php
        $canBook = $room['status'] === 'kosong';
        if ($room['status'] === 'terisi')  { $stLabel = 'Terisi';          $stClass = 'status-terisi';  }
        elseif ($room['status'] === 'booking') { $stLabel = 'Sedang Dipesan'; $stClass = 'status-booking'; }
        else                               { $stLabel = 'Tersedia';        $stClass = 'status-kosong';  }
      ?>
      <div class="room-card">
        <img src="<?= e($room['foto']) ?>" alt="Kamar <?= e($room['nomor']) ?>"
             onerror="this.src='assets/images/kamar-a1.jpg'">
        <div class="room-card-body">
          <h3>Kamar <?= e($room['nomor']) ?></h3>
          <p style="font-size:13px;color:var(--muted);">
            Tipe: <?= e($room['tipe']) ?> &nbsp;·&nbsp; 📐 <?= e($room['ukuran'] ?? '-') ?>
          </p>
          <p class="price"><?= formatRupiah($room['harga']) ?> / bulan</p>
          <span class="status <?= $stClass ?>"><?= $stLabel ?></span>

          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
            <a href="detail-kamar.php?id=<?= $room['id'] ?>" class="btn-secondary" style="margin-top:0;">
              Lihat Detail
            </a>
            <?php if (!$canBook): ?>
              <span class="btn-disabled" style="margin-top:0;">
                <?= $room['status'] === 'terisi' ? 'Sudah Terisi' : 'Sedang Dipesan' ?>
              </span>
            <?php elseif (!$user): ?>
              <a href="login.php" class="btn-primary" style="margin-top:0;">Login untuk Booking</a>
            <?php elseif ($user['role'] === 'admin'): ?>
              <span class="btn-disabled" style="margin-top:0;">Admin</span>
            <?php else: ?>
              <a href="detail-kamar.php?id=<?= $room['id'] ?>" class="btn-primary" style="margin-top:0;">
                Booking Sekarang
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
