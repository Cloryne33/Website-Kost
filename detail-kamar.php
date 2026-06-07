<?php
require_once 'includes/config.php';
startSession();
$active = 'kamar';
$user   = getLoggedInUser();

// Ambil kamar
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: kamar.php'); exit; }

$db   = getDB();
$stmt = $db->prepare('SELECT * FROM rooms WHERE id = ?');
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) { header('Location: kamar.php'); exit; }

// Fasilitas
$facKamar  = parseFacility($room['fasilitas_kamar']);
$facKM     = parseFacility($room['fasilitas_kamar_mandi']);
$facUmum   = parseFacility($room['fasilitas_umum']);
$facParkir = parseFacility($room['fasilitas_parkir']);

// Status
$canBook = $room['status'] === 'kosong';
if ($room['status'] === 'terisi')       { $stLabel = 'Terisi';          $stClass = 'status-terisi';  }
elseif ($room['status'] === 'booking')  { $stLabel = 'Sedang Dipesan';  $stClass = 'status-booking'; }
else                                    { $stLabel = 'Tersedia';         $stClass = 'status-kosong';  }

// Handle booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'booking') {
    if (!$user)                    { header('Location: login.php'); exit; }
    if ($user['role'] === 'admin') { $_SESSION['flash'] = ['type'=>'error','msg'=>'Admin tidak bisa booking.']; header('Location: detail-kamar.php?id='.$id); exit; }

    // Re-check status (race condition safe)
    $stmt2 = $db->prepare('SELECT status FROM rooms WHERE id = ? FOR UPDATE');
    $db->beginTransaction();
    $stmt2->execute([$id]);
    $fresh = $stmt2->fetch();
    if ($fresh['status'] !== 'kosong') {
        $db->rollBack();
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Maaf, kamar ini sudah tidak tersedia saat ini.'];
        header('Location: detail-kamar.php?id='.$id);
        exit;
    }

    // Validasi input
    $name    = trim($_POST['bd_name']     ?? '');
    $phone   = trim($_POST['bd_phone']    ?? '');
    $email   = trim($_POST['bd_email']    ?? '');
    $date    = trim($_POST['bd_date']     ?? '');
    $dur     = (int)($_POST['bd_duration']?? 1);
    $pay     = trim($_POST['bd_payment']  ?? 'bca');
    $note    = trim($_POST['bd_note']     ?? '');
    $dur     = max(1, min(12, $dur));
    $total   = $room['harga'] * $dur;
    $code    = generateBookingCode();

    if (!$name || !$phone || !$email || !$date) {
        $db->rollBack();
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Mohon lengkapi semua data diri.'];
        header('Location: detail-kamar.php?id='.$id);
        exit;
    }

    // Proses upload bukti bayar (wajib)
    $buktiPath = null;
    if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
        $db->rollBack();
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Mohon upload bukti pembayaran terlebih dahulu.'];
        header('Location: detail-kamar.php?id='.$id);
        exit;
    }
    $uploadDir = 'assets/uploads/bukti/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg','jpeg','png','gif','webp','pdf'];
    if (!in_array($ext, $allowedExt)) {
        $db->rollBack();
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Format file tidak didukung. Gunakan JPG, PNG, GIF, WebP, atau PDF.'];
        header('Location: detail-kamar.php?id='.$id);
        exit;
    }
    $filename  = 'bukti-' . $code . '.' . $ext;
    $dest      = $uploadDir . $filename;
    if (!move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $dest)) {
        $db->rollBack();
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Gagal menyimpan bukti pembayaran. Coba lagi.'];
        header('Location: detail-kamar.php?id='.$id);
        exit;
    }
    $buktiPath = $dest;

    // Simpan booking
    $ins = $db->prepare('INSERT INTO bookings
        (booking_code, room_id, user_name, user_email, user_phone, check_in_date, duration, total, payment_method, note, bukti_bayar, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $ins->execute([$code, $id, $name, $email, $phone, $date, $dur, $total, $pay, $note, $buktiPath, 'menunggu_konfirmasi']);

    // Update status kamar
    $db->prepare('UPDATE rooms SET status = ? WHERE id = ?')->execute(['booking', $id]);
    $db->commit();

    $_SESSION['flash']       = ['type'=>'success','msg'=>'Booking berhasil! Kode booking kamu: <strong>'.$code.'</strong>. Silakan lakukan pembayaran dan tunggu konfirmasi admin.'];
    $_SESSION['last_booking'] = $code;
    header('Location: detail-kamar.php?id='.$id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kamar <?= e($room['nomor']) ?> - <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container section">

  <div class="detail-card">
    <img src="<?= e($room['foto']) ?>" alt="Kamar <?= e($room['nomor']) ?>" class="detail-image"
         onerror="this.src='assets/images/kamar-a1.jpg'">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:8px;">
      <div>
        <h2 style="margin-bottom:4px;">Kamar <?= e($room['nomor']) ?></h2>
        <p style="color:var(--muted);font-size:14px;">Tipe: <strong><?= e($room['tipe']) ?></strong></p>
      </div>
      <div style="text-align:right;">
        <p class="price" style="margin:0;"><?= formatRupiah($room['harga']) ?> / bulan</p>
        <?= statusBadge($room['status']) ?>
      </div>
    </div>

    <?php if ($room['ukuran']): ?>
      <div class="ukuran-badge">📐 <?= e($room['ukuran']) ?></div>
    <?php endif; ?>

    <!-- Fasilitas -->
    <h3 style="margin-top:20px;margin-bottom:14px;">Fasilitas</h3>

    <?php if ($facKamar): ?>
      <div class="fac-section">
        <div class="fac-section-title">🛏 Fasilitas Kamar</div>
        <div class="fac-tags">
          <?php foreach ($facKamar as $f): ?>
            <span class="fac-tag"><?= e($f) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($facKM): ?>
      <div class="fac-section">
        <div class="fac-section-title">🚿 Kamar Mandi</div>
        <div class="fac-tags">
          <?php foreach ($facKM as $f): ?>
            <span class="fac-tag fac-tag-km"><?= e($f) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($facUmum): ?>
      <div class="fac-section">
        <div class="fac-section-title">🏠 Fasilitas Umum</div>
        <div class="fac-tags">
          <?php foreach ($facUmum as $f): ?>
            <span class="fac-tag fac-tag-umum"><?= e($f) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($facParkir): ?>
      <div class="fac-section">
        <div class="fac-section-title">🚗 Parkir</div>
        <div class="fac-tags">
          <?php foreach ($facParkir as $f): ?>
            <span class="fac-tag fac-tag-parkir"><?= e($f) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <h3 style="margin-top:22px;margin-bottom:8px;">Deskripsi</h3>
    <p><?= e($room['deskripsi'] ?? '') ?></p>

    <div style="margin-top:24px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <?php if (!$canBook): ?>
        <span class="btn-disabled">
          <?= $room['status'] === 'terisi' ? 'Kamar Sudah Terisi' : 'Sedang Dipesan' ?>
        </span>
      <?php elseif (!$user): ?>
        <a href="login.php" class="btn-primary">Login untuk Booking</a>
      <?php elseif ($user['role'] === 'admin'): ?>
        <span class="btn-disabled">Admin Tidak Bisa Booking</span>
      <?php else: ?>
        <button class="btn-primary" onclick="openBookingModal()">
          Booking Kamar Ini
        </button>
      <?php endif; ?>
      <a href="kamar.php" class="btn-secondary">← Kembali</a>
    </div>
  </div>
</main>

<?php if ($canBook && $user && $user['role'] !== 'admin'): ?>
<!-- ╔══════════════════════════════════════╗ -->
<!-- ║         BOOKING MODAL (4 STEP)      ║ -->
<!-- ╚══════════════════════════════════════╝ -->
<div id="bookingModal" class="booking-overlay">
  <div class="booking-modal">

    <div class="modal-header">
      <h3 id="modalTitle">Booking Kamar</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>

    <!-- Step Indicator -->
    <div class="modal-body" style="padding-bottom:0;">
      <div class="step-indicator">
        <div class="step-item active"  id="dot1"><div class="step-circle">1</div><div class="step-label">Data Diri</div></div>
        <div class="step-item"         id="dot2"><div class="step-circle">2</div><div class="step-label">Pembayaran</div></div>
        <div class="step-item"         id="dot3"><div class="step-circle">3</div><div class="step-label">Konfirmasi</div></div>
        <div class="step-item"         id="dot4"><div class="step-circle">✓</div><div class="step-label">Selesai</div></div>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
      <input type="hidden" name="action" value="booking">

    <div class="modal-body" style="padding-top:16px;">

      <!-- ── STEP 1: Data Diri ─────────────────── -->
      <div class="step-panel active" id="panel1">
        <div class="room-summary">
          <div>
            <div class="room-summary-name">Kamar <?= e($room['nomor']) ?> — <?= e($room['tipe']) ?></div>
            <div style="font-size:13px;color:var(--muted);"><?= SITE_NAME ?></div>
          </div>
          <div class="room-summary-price"><?= formatRupiah($room['harga']) ?>/bln</div>
        </div>
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input type="text" name="bd_name" id="bd_name"
                 value="<?= e($user['name']) ?>" placeholder="Nama sesuai KTP">
        </div>
        <div class="form-group">
          <label>Nomor WhatsApp</label>
          <input type="text" name="bd_phone" id="bd_phone"
                 value="<?= e($user['phone']) ?>" placeholder="6285212345678">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="bd_email" id="bd_email"
                 value="<?= e($user['email']) ?>" placeholder="email@kamu.com">
        </div>
        <div class="form-group">
          <label>Tanggal Masuk</label>
          <input type="date" name="bd_date" id="bd_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Durasi Sewa</label>
          <select name="bd_duration" id="bd_duration" onchange="updateTotal()">
            <option value="1">1 Bulan</option>
            <option value="3">3 Bulan</option>
            <option value="6">6 Bulan</option>
            <option value="12">12 Bulan</option>
          </select>
        </div>
        <div class="form-group">
          <label>Catatan (opsional)</label>
          <textarea name="bd_note" placeholder="Permintaan khusus..."></textarea>
        </div>
      </div>

      <!-- ── STEP 2: Pilih Pembayaran ──────────── -->
      <div class="step-panel" id="panel2">
        <div class="room-summary">
          <div>
            <div class="room-summary-name">Kamar <?= e($room['nomor']) ?> — <?= e($room['tipe']) ?></div>
            <div style="font-size:13px;color:var(--muted);" id="durLabel">1 Bulan × <?= formatRupiah($room['harga']) ?></div>
          </div>
          <div class="room-summary-price" id="totalLabel"><?= formatRupiah($room['harga']) ?></div>
        </div>

        <p style="font-weight:600;margin-bottom:14px;font-size:14px;">Pilih Metode Pembayaran</p>

        <input type="hidden" name="bd_payment" id="bd_payment" value="bca">
        <div class="payment-methods">
          <?php
          $payOpts = [
            'bca'     => ['icon'=>'🏦','name'=>'Transfer BCA',    'desc'=>'Bank Central Asia'],
            'bni'     => ['icon'=>'🏛️','name'=>'Transfer BNI',    'desc'=>'Bank Negara Indonesia'],
            'mandiri' => ['icon'=>'🔵','name'=>'Transfer Mandiri','desc'=>'Bank Mandiri'],
            'qris'    => ['icon'=>'📱','name'=>'QRIS',             'desc'=>'GoPay, OVO, Dana, dll.'],
          ];
          foreach ($payOpts as $val => $opt): ?>
          <div class="payment-option <?= $val==='bca'?'selected':'' ?>"
               onclick="selectPayment('<?= $val ?>', this)">
            <div class="payment-icon"><?= $opt['icon'] ?></div>
            <div class="payment-info">
              <div class="payment-name"><?= $opt['name'] ?></div>
              <div class="payment-desc"><?= $opt['desc'] ?></div>
            </div>
            <div class="payment-check"><?= $val==='bca'?'✓':'' ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ── STEP 3: Instruksi Pembayaran ─────── -->
      <div class="step-panel" id="panel3">
        <div id="paymentInstructions"><!-- diisi JS --></div>

        <div class="note-box">
          ⚠️ <span>Lakukan pembayaran dalam <strong>2×24 jam</strong>. Booking dibatalkan otomatis jika melewati batas waktu.</span>
        </div>

        <div class="form-group">
          <label>Upload Bukti Pembayaran <span style="color:var(--danger)">*</span></label>
          <div class="upload-proof" onclick="document.getElementById('proofInput').click()">
            <div class="upload-proof-icon">📎</div>
            <div class="upload-proof-text" id="proofLabel">Klik untuk upload foto bukti transfer</div>
            <input type="file" name="bukti_bayar" id="proofInput" accept="image/*"
                   onchange="previewProof(event)" style="display:none;">
          </div>
          <div id="uploadedPreview"></div>
          <div id="proofError" style="color:var(--danger);font-size:13px;font-weight:500;margin-top:6px;display:none;">
            ⚠️ Wajib upload bukti pembayaran
          </div>
        </div>

        <div class="wa-box">
          <div class="wa-box-icon">💬</div>
          <div>
            <div class="wa-box-title">Punya pertanyaan?</div>
            <div class="wa-box-text">Hubungi <strong>Service Center</strong> kami jika ada kendala pembayaran.</div>
            <a href="<?= WA_URL ?>?text=Halo%20saya%20ingin%20bertanya%20tentang%20booking" target="_blank" class="btn-whatsapp" style="margin-top:8px;">
              Hubungi WhatsApp →
            </a>
          </div>
        </div>
      </div>

      <!-- ── STEP 4: Selesai (submit form) ─────── -->
      <div class="step-panel" id="panel4">
        <!-- ini tidak pernah tampil di browser; form submit langsung di step 3 -->
      </div>

    </div><!-- modal-body -->

    <div class="modal-footer">
      <button type="button" class="btn-secondary" id="btnBack" style="display:none;" onclick="prevStep()">← Kembali</button>
      <button type="button" class="btn-primary"   id="btnNext" onclick="nextStep()">Lanjut →</button>
      <button type="submit" class="btn-payment"   id="btnSubmit" style="display:none;">Konfirmasi Booking ✓</button>
    </div>

    </form>
  </div>
</div>

<script>
const HARGA = <?= (int)$room['harga'] ?>;
let step = 1;
let selectedPay = 'bca';
let fileUploaded = false;

const payAccounts = {
  bca:     { bank:'BCA',     number:'1234567890', holder:'Apik Singgah Sini', icon:'🏦' },
  bni:     { bank:'BNI',     number:'9876543210', holder:'Apik Singgah Sini', icon:'🏛️' },
  mandiri: { bank:'Mandiri', number:'1122334455', holder:'Apik Singgah Sini', icon:'🔵' },
  qris:    { bank:'QRIS',    number:null,         holder:'Apik Singgah Sini', icon:'📱' },
};

function formatRp(n) {
  return 'Rp ' + n.toLocaleString('id-ID');
}

function updateTotal() {
  const dur   = parseInt(document.getElementById('bd_duration').value) || 1;
  const total = HARGA * dur;
  document.getElementById('durLabel').textContent   = dur + ' Bulan × ' + formatRp(HARGA);
  document.getElementById('totalLabel').textContent = formatRp(total);
}

function selectPayment(val, el) {
  document.querySelectorAll('.payment-option').forEach(o => {
    o.classList.remove('selected');
    o.querySelector('.payment-check').textContent = '';
  });
  el.classList.add('selected');
  el.querySelector('.payment-check').textContent = '✓';
  selectedPay = val;
  document.getElementById('bd_payment').value = val;
}

function renderPaymentInstructions() {
  const dur   = parseInt(document.getElementById('bd_duration').value) || 1;
  const total = HARGA * dur;
  const acc   = payAccounts[selectedPay];
  const box   = document.getElementById('paymentInstructions');

  let html = `<div class="payment-detail-box">
    <div class="payment-detail-title">${acc.icon} Instruksi Transfer ${acc.bank}</div>`;

  if (selectedPay === 'qris') {
    html += `<div style="text-align:center;padding:16px;">
      <img src="assets/images/payments/qr-qris.png" alt="QRIS"
           style="max-width:220px;border-radius:12px;margin-bottom:12px;box-shadow:0 4px 16px rgba(0,0,0,0.1);"
           onerror="this.style.display='none'">
      <div style="font-weight:600;margin-bottom:4px;">Scan QRIS di atas</div>
      <div style="font-size:13px;color:var(--muted);">Buka GoPay / OVO / Dana / m-Banking, lalu scan QR.</div>
    </div>`;
  } else {
    html += `<div class="bank-account">
      <div class="bank-info">
        <div class="bank-name">Bank ${acc.bank}</div>
        <div class="account-number">${acc.number}</div>
        <div class="account-holder">a.n. ${acc.holder}</div>
      </div>
      <button type="button" class="copy-btn"
              onclick="navigator.clipboard.writeText('${acc.number}').then(()=>alert('Disalin!'))">
        Salin
      </button>
    </div>`;
  }

  html += `<div class="amount-box" style="margin-top:12px;">
    <div><div class="amount-label">Total yang harus dibayar</div>
    <div class="amount-value">${formatRp(total)}</div></div>
    <button type="button" class="copy-btn" style="background:rgba(255,255,255,0.2);color:white;"
            onclick="navigator.clipboard.writeText('${total}').then(()=>alert('Disalin!'))">
      Salin
    </button>
  </div></div>`;

  box.innerHTML = html;
}

function renderStep(s) {
  for (let i=1;i<=4;i++) {
    document.getElementById('panel'+i)?.classList.remove('active');
    const dot = document.getElementById('dot'+i);
    if (dot) { dot.classList.remove('active','completed'); if(i<s) dot.classList.add('completed'); if(i===s) dot.classList.add('active'); }
  }
  document.getElementById('panel'+s)?.classList.add('active');

  const btnBack   = document.getElementById('btnBack');
  const btnNext   = document.getElementById('btnNext');
  const btnSubmit = document.getElementById('btnSubmit');
  const titles    = {1:'Data Diri',2:'Pilih Pembayaran',3:'Lakukan Pembayaran'};

  document.getElementById('modalTitle').textContent = titles[s] || 'Booking';
  btnBack.style.display   = s > 1 ? 'inline-block' : 'none';
  btnNext.style.display   = s < 3 ? 'inline-block' : 'none';
  btnSubmit.style.display = s === 3 ? 'inline-block' : 'none';
  if (s === 3) {
    btnSubmit.disabled = !fileUploaded;
  }

  if (s===2) updateTotal();
  if (s===3) renderPaymentInstructions();
}

function nextStep() {
  if (step===1) {
    const name  = document.getElementById('bd_name').value.trim();
    const phone = document.getElementById('bd_phone').value.trim();
    const email = document.getElementById('bd_email').value.trim();
    const date  = document.getElementById('bd_date').value;
    if (!name||!phone||!email||!date) { alert('Mohon lengkapi semua data diri.'); return; }
    if (!email.includes('@'))          { alert('Format email tidak valid.'); return; }
  }
  if (step < 3) { step++; renderStep(step); }
}

function prevStep() {
  if (step > 1) { step--; renderStep(step); }
}

function resetUploadState() {
  fileUploaded = false;
  const input = document.getElementById('proofInput');
  if (input) input.value = '';
  const label = document.getElementById('proofLabel');
  if (label) label.textContent = 'Klik untuk upload foto bukti transfer';
  const preview = document.getElementById('uploadedPreview');
  if (preview) preview.innerHTML = '';
  const error = document.getElementById('proofError');
  if (error) error.style.display = 'none';
  const btn = document.getElementById('btnSubmit');
  if (btn) btn.disabled = true;
}

function openBookingModal() {
  step = 1;
  resetUploadState();
  renderStep(1);
  document.getElementById('bookingModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('bookingModal').classList.remove('active');
  document.body.style.overflow = '';
  step = 1;
  resetUploadState();
  renderStep(1);
}

function previewProof(e) {
  const file = e.target.files[0];
  if (!file) return;
  fileUploaded = true;
  document.getElementById('proofLabel').textContent = '✅ ' + file.name;
  document.getElementById('proofError').style.display = 'none';
  document.getElementById('btnSubmit').disabled = false;
  const reader = new FileReader();
  reader.onload = ev => {
    document.getElementById('uploadedPreview').innerHTML =
      '<img src="'+ev.target.result+'" style="max-width:100%;border-radius:8px;max-height:160px;margin-top:8px;">';
  };
  reader.readAsDataURL(file);
}

function validateForm() {
  if (!fileUploaded) {
    document.getElementById('proofError').style.display = 'block';
    return false;
  }
  return true;
}

// Close on overlay click
document.getElementById('bookingModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
</body>
</html>
