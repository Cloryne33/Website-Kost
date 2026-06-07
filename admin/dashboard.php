<?php
require_once '../includes/config.php';
$user   = requireAdmin('../index.php');
$active = 'admin';
$base   = '../';

$db = getDB();

// ── Handle POST actions ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_room_status') {
        $rid    = (int)$_POST['room_id'];
        $status = $_POST['status'];
        if (in_array($status, ['kosong','booking','terisi'])) {
            $db->prepare('UPDATE rooms SET status = ? WHERE id = ?')->execute([$status, $rid]);
        }
        header('Location: dashboard.php?tab=rooms');
        exit;
    }

    if ($action === 'accept_booking') {
        $bid = (int)$_POST['booking_id'];
        $rid = (int)$_POST['room_id'];
        $db->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['diterima', $bid]);
        $db->prepare('UPDATE rooms    SET status = ? WHERE id = ?')->execute(['terisi',   $rid]);
        header('Location: dashboard.php?tab=bookings');
        exit;
    }

    if ($action === 'reject_booking') {
        $bid = (int)$_POST['booking_id'];
        $rid = (int)$_POST['room_id'];
        $db->prepare('UPDATE bookings SET status = ? WHERE id = ?')->execute(['ditolak', $bid]);
        $db->prepare('UPDATE rooms    SET status = ? WHERE id = ?')->execute(['kosong',  $rid]);
        header('Location: dashboard.php?tab=bookings');
        exit;
    }
}

// ── Fetch data ────────────────────────────────────
$rooms    = $db->query('SELECT * FROM rooms ORDER BY nomor')->fetchAll();
$bookings = $db->query('SELECT b.*, r.nomor as room_nomor, r.tipe as room_tipe
                        FROM bookings b JOIN rooms r ON b.room_id = r.id
                        ORDER BY b.created_at DESC')->fetchAll();

// Stats
$total   = count($rooms);
$kosong  = count(array_filter($rooms, fn($r) => $r['status']==='kosong'));
$booking = count(array_filter($rooms, fn($r) => $r['status']==='booking'));
$terisi  = count(array_filter($rooms, fn($r) => $r['status']==='terisi'));
$pending = count(array_filter($bookings, fn($b) => $b['status']==='menunggu_konfirmasi'));

$tab = $_GET['tab'] ?? 'bookings';

$payLabel = ['bca'=>'Transfer BCA','bni'=>'Transfer BNI','mandiri'=>'Transfer Mandiri','qris'=>'QRIS'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .tab-bar { display:flex; gap:6px; margin-bottom:24px; border-bottom:2px solid var(--border); padding-bottom:0; }
    .tab-btn { padding:10px 20px; border:none; background:none; cursor:pointer; font-family:'DM Sans',sans-serif;
               font-size:14px; font-weight:600; color:var(--muted); border-bottom:3px solid transparent;
               margin-bottom:-2px; transition:all .2s; }
    .tab-btn.active { color:var(--purple); border-bottom-color:var(--purple); }
    .tab-btn:hover  { color:var(--purple); }
    .tab-pane { display:none; } .tab-pane.active { display:block; }
    .badge-pending { background:var(--warning-light);color:var(--warning);border-radius:20px;
                     padding:2px 8px;font-size:11px;font-weight:700;margin-left:6px; }
  </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<main class="container section">
  <h2>Dashboard Admin</h2>
  <p class="text-muted">Kelola kamar dan konfirmasi booking penghuni.</p>

  <div class="user-info-box" style="margin-bottom:24px;">
    Login sebagai admin: <strong><?= e($user['name']) ?></strong>
    &nbsp;|&nbsp; Email: <strong><?= e($user['email']) ?></strong>
  </div>

  <!-- Stats -->
  <div class="dashboard-grid" style="margin-bottom:32px;">
    <div class="dashboard-card"><h3>Total Kamar</h3><p><?= $total ?></p></div>
    <div class="dashboard-card"><h3>Kamar Kosong</h3><p style="color:var(--success)"><?= $kosong ?></p></div>
    <div class="dashboard-card"><h3>Sedang Dipesan</h3><p style="color:var(--warning)"><?= $booking ?></p></div>
    <div class="dashboard-card"><h3>Terisi</h3><p style="color:var(--danger)"><?= $terisi ?></p></div>
    <div class="dashboard-card"><h3>Booking Pending</h3><p style="color:var(--pink)"><?= $pending ?></p></div>
  </div>

  <!-- Tabs -->
  <div class="tab-bar">
    <button class="tab-btn <?= $tab==='bookings'?'active':'' ?>" onclick="showTab('bookings')">
      📋 Data Booking
      <?php if ($pending): ?><span class="badge-pending"><?= $pending ?></span><?php endif; ?>
    </button>
    <button class="tab-btn <?= $tab==='rooms'?'active':'' ?>" onclick="showTab('rooms')">
      🏠 Kelola Kamar
    </button>
  </div>

  <!-- Tab: Bookings -->
  <div class="tab-pane <?= $tab==='bookings'?'active':'' ?>" id="tab-bookings">
    <div class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Kode</th><th>Kamar</th><th>Nama</th><th>Email</th>
            <th>No. WA</th><th>Check-in</th><th>Durasi</th>
            <th>Total</th><th>Metode</th><th>Bukti</th><th>Status</th><th>Tgl Booking</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$bookings): ?>
            <tr><td colspan="13" style="text-align:center;padding:24px;color:var(--muted);">Belum ada booking.</td></tr>
          <?php else: ?>
            <?php foreach ($bookings as $b): ?>
            <tr>
              <td style="font-weight:700;color:var(--purple);font-size:12px;"><?= e($b['booking_code']) ?></td>
              <td><?= e($b['room_nomor']) ?> <small style="color:var(--muted)">(<?= e($b['room_tipe']) ?>)</small></td>
              <td><?= e($b['user_name']) ?></td>
              <td style="font-size:12px;"><?= e($b['user_email']) ?></td>
              <td><?= e($b['user_phone']) ?></td>
              <td><?= e($b['check_in_date']) ?></td>
              <td><?= $b['duration'] ?> bln</td>
              <td><?= formatRupiah($b['total']) ?></td>
              <td><?= e($payLabel[$b['payment_method']] ?? $b['payment_method']) ?></td>
              <td>
                <?php if ($b['bukti_bayar']): ?>
                  <a href="../<?= e($b['bukti_bayar']) ?>" target="_blank" class="btn-small btn-edit" style="text-decoration:none;">
                    Lihat
                  </a>
                <?php else: ?>
                  <span style="color:var(--muted);font-size:12px;">—</span>
                <?php endif; ?>
              </td>
              <td><?= statusBadge($b['status']) ?></td>
              <td style="font-size:12px;"><?= e($b['created_at']) ?></td>
              <td>
                <?php if ($b['status'] === 'menunggu_konfirmasi'): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action"     value="accept_booking">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="room_id"    value="<?= $b['room_id'] ?>">
                    <button type="submit" class="btn-small btn-edit"
                            onclick="return confirm('Terima booking ini? Kamar akan jadi Terisi.')">
                      Terima
                    </button>
                  </form>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action"     value="reject_booking">
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                    <input type="hidden" name="room_id"    value="<?= $b['room_id'] ?>">
                    <button type="submit" class="btn-small btn-delete"
                            onclick="return confirm('Tolak booking ini? Kamar akan kembali kosong.')">
                      Tolak
                    </button>
                  </form>
                <?php else: ?>
                  <span style="color:var(--muted);font-size:12px;">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab: Rooms -->
  <div class="tab-pane <?= $tab==='rooms'?'active':'' ?>" id="tab-rooms">
    <div class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th><th>Foto</th><th>Nomor</th><th>Tipe</th>
            <th>Ukuran</th><th>Harga</th><th>Status</th><th>Ubah Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rooms as $i => $room): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td>
              <img src="../<?= e($room['foto']) ?>" class="table-image"
                   onerror="this.src='../assets/images/kamar-a1.jpg'">
            </td>
            <td><strong><?= e($room['nomor']) ?></strong></td>
            <td><?= e($room['tipe']) ?></td>
            <td><?= e($room['ukuran'] ?? '-') ?></td>
            <td><?= formatRupiah($room['harga']) ?></td>
            <td><?= statusBadge($room['status']) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action"  value="update_room_status">
                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                <select name="status" class="status-select"
                        onchange="this.form.submit()">
                  <option value="kosong"  <?= $room['status']==='kosong' ?'selected':'' ?>>Kosong</option>
                  <option value="booking" <?= $room['status']==='booking'?'selected':'' ?>>Booking</option>
                  <option value="terisi"  <?= $room['status']==='terisi' ?'selected':'' ?>>Terisi</option>
                </select>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<?php include '../includes/footer.php'; ?>

<script>
function showTab(name) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  event.target.classList.add('active');
}
</script>
</body>
</html>
