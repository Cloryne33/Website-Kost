<?php
require_once '../includes/config.php';
$user = requireAdmin('../index.php');
$active = 'admin';
$base   = '../';

$db = getDB();

// ── Handle form submit ────────────────────────────
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['bca_number','bca_holder','bni_number','bni_holder',
             'mandiri_number','mandiri_holder','qris_holder'];

    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value)
                          VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');

    foreach ($keys as $key) {
        $val = trim($_POST[$key] ?? '');
        $stmt->execute([$key, $val]);
    }

    // ── Handle QRIS image upload ──────────────────
    if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/png','image/jpeg','image/jpg','image/webp'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fileInfo, $_FILES['qris_image']['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mime, $allowed)) {
            $err = 'File QRIS harus berupa gambar (PNG/JPG/WEBP).';
        } else {
            $uploadDir = __DIR__ . '/../assets/images/payments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION);
            $filename = 'qr-qris-uploaded.' . $ext;
            $destPath = 'assets/images/payments/' . $filename;

            if (move_uploaded_file($_FILES['qris_image']['tmp_name'], $uploadDir . $filename)) {
                $stmt->execute(['qris_image', $destPath]);
                $msg = 'Pengaturan berhasil disimpan.';
            } else {
                $err = 'Gagal mengupload file QRIS.';
            }
        }
    } else {
        $msg = 'Pengaturan berhasil disimpan.';
    }
}

// ── Fetch current settings ────────────────────────
$rows = $db->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = [];
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$qrisImage = $settings['qris_image'] ?? 'assets/images/payments/qr-qris.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengaturan Pembayaran - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .settings-section { max-width:700px; margin:0 auto; }
    .settings-card { background:var(--card-bg); border-radius:12px; padding:24px; margin-bottom:20px;
                     border:1px solid var(--border); }
    .settings-card h3 { margin-top:0; margin-bottom:16px; font-size:16px; }
    .form-row { display:flex; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
    .form-row .form-group { flex:1; min-width:180px; }
    .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:4px; color:var(--muted); }
    .form-group input { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:8px;
                        font-size:14px; font-family:'DM Sans',sans-serif; box-sizing:border-box; }
    .form-group input:focus { outline:none; border-color:var(--purple); box-shadow:0 0 0 3px var(--purple-light); }
    .qris-preview { max-width:200px; border-radius:8px; margin:12px 0; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
    .btn-save { background:var(--purple); color:white; border:none; padding:12px 32px; border-radius:8px;
                font-size:15px; font-weight:600; cursor:pointer; font-family:'DM Sans',sans-serif;
                transition:background .2s; }
    .btn-save:hover { background:var(--purple-dark); }
    .msg-success { background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:16px;
                   font-weight:500; }
    .msg-error   { background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;
                   font-weight:500; }
    .current-qr-label { font-size:13px; color:var(--muted); margin-top:8px; }
  </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<main class="container section">
  <div class="settings-section">
    <h2>Pengaturan Pembayaran</h2>
    <p class="text-muted">Edit nomor rekening, nama pemilik, dan gambar QRIS dari sini.</p>

    <?php if ($msg): ?>
      <div class="msg-success"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="msg-error"><?= e($err) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <!-- ── BCA ── -->
      <div class="settings-card">
        <h3>🏦 BCA</h3>
        <div class="form-row">
          <div class="form-group">
            <label>No. Rekening</label>
            <input type="text" name="bca_number" value="<?= e($settings['bca_number'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Atas Nama</label>
            <input type="text" name="bca_holder" value="<?= e($settings['bca_holder'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <!-- ── BNI ── -->
      <div class="settings-card">
        <h3>🏛️ BNI</h3>
        <div class="form-row">
          <div class="form-group">
            <label>No. Rekening</label>
            <input type="text" name="bni_number" value="<?= e($settings['bni_number'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Atas Nama</label>
            <input type="text" name="bni_holder" value="<?= e($settings['bni_holder'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <!-- ── Mandiri ── -->
      <div class="settings-card">
        <h3>🔵 Mandiri</h3>
        <div class="form-row">
          <div class="form-group">
            <label>No. Rekening</label>
            <input type="text" name="mandiri_number" value="<?= e($settings['mandiri_number'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Atas Nama</label>
            <input type="text" name="mandiri_holder" value="<?= e($settings['mandiri_holder'] ?? '') ?>" required>
          </div>
        </div>
      </div>

      <!-- ── QRIS ── -->
      <div class="settings-card">
        <h3>📱 QRIS</h3>
        <div class="form-row">
          <div class="form-group">
            <label>Atas Nama</label>
            <input type="text" name="qris_holder" value="<?= e($settings['qris_holder'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label>Gambar QRIS</label>
          <div>
            <img src="../<?= e($qrisImage) ?>" alt="QRIS Preview" class="qris-preview"
                 onerror="this.style.display='none'">
          </div>
          <div class="current-qr-label">Saat ini: <?= e(basename($qrisImage)) ?></div>
          <input type="file" name="qris_image" accept="image/png,image/jpeg,image/jpg,image/webp"
                 style="margin-top:8px;">
          <div style="font-size:12px;color:var(--muted);margin-top:4px;">Kosongkan jika tidak ingin mengganti gambar QRIS.</div>
        </div>
      </div>

      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn-save">Simpan Pengaturan</button>
        <a href="dashboard.php" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;padding:0 24px;">← Kembali</a>
      </div>
    </form>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
