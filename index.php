<?php
require_once 'includes/config.php';
startSession();
$active = 'beranda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= SITE_NAME ?> - Kost Nyaman & Terjangkau</title>
  <link rel="icon" type="image/png" href="assets/images/logo-apik.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<section class="hero">
  <div class="container hero-content">
    <h2>Kamar Kost Apik,<br>Harga Bersahabat</h2>
    <p>Temukan kamar kost nyaman di lokasi strategis. Lihat harga, fasilitas,
       dan status kamar secara langsung — booking mudah tanpa repot.</p>
    <a href="kamar.php" class="btn-primary">Lihat Kamar Tersedia</a>
  </div>
</section>

<section class="container section">
  <h2>Fasilitas Unggulan</h2>
  <div class="facility-grid">
    <div class="facility-card"><div class="facility-icon">📶</div>WiFi</div>
    <div class="facility-card"><div class="facility-icon">🚗</div>Parkir Motor &amp; Mobil</div>
    <div class="facility-card"><div class="facility-icon">🍳</div>Dapur Bersama</div>
    <div class="facility-card"><div class="facility-icon">🛋️</div>Ruang Tamu</div>
    <div class="facility-card"><div class="facility-icon">👕</div>Ruang Jemuran</div>
    <div class="facility-card"><div class="facility-icon">🥶</div>Kulkas &amp; Dispenser</div>
    <div class="facility-card"><div class="facility-icon">🔒</div>Penjaga Kos</div>
    <div class="facility-card"><div class="facility-icon">🧹</div>Pengurus Kos</div>
  </div>
</section>

<section class="container section">
  <h2>Pilihan Tipe Kamar</h2>
  <div class="facility-grid">
    <div class="facility-card" style="text-align:left;padding:22px;">
      <div class="facility-icon">🛏️</div>
      <div style="font-size:18px;margin-bottom:6px;">Standar</div>
      <div style="font-size:22px;font-weight:700;color:var(--purple);margin-bottom:8px;">
        Rp 1.450.000<span style="font-size:13px;font-weight:400;color:var(--muted);">/bulan</span>
      </div>
      <div style="font-size:13px;color:var(--muted);">📐 3×4 meter &nbsp;·&nbsp; K. Mandi Luar</div>
    </div>
    <div class="facility-card" style="text-align:left;padding:22px;border:2px solid var(--purple);">
      <div class="facility-icon">🏠</div>
      <div style="font-size:18px;margin-bottom:6px;">Reguler</div>
      <div style="font-size:22px;font-weight:700;color:var(--purple);margin-bottom:8px;">
        Rp 1.600.000<span style="font-size:13px;font-weight:400;color:var(--muted);">/bulan</span>
      </div>
      <div style="font-size:13px;color:var(--muted);">📐 3×6 meter &nbsp;·&nbsp; K. Mandi Dalam + Air Panas</div>
    </div>
  </div>
  <div style="margin-top:24px;">
    <a href="kamar.php" class="btn-primary">Lihat Semua Kamar</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>
