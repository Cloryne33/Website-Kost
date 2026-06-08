<?php
require_once 'includes/config.php';
require_once 'includes/google_config.php';
startSession();

// User sudah login → redirect
if (getLoggedInUser()) {
    header('Location: index.php');
    exit;
}

// Cek error dari Google
if (isset($_GET['error'])) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Login dengan Google dibatalkan atau gagal.'];
    header('Location: login.php');
    exit;
}

// Cek state (CSRF protection)
$state = $_GET['state'] ?? '';
if (!$state || !isset($_SESSION['google_state']) || $state !== $_SESSION['google_state']) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Token tidak valid. Silakan coba lagi.'];
    header('Location: login.php');
    exit;
}
unset($_SESSION['google_state']);

// Tukar authorization code dengan access token
$code = $_GET['code'] ?? '';
if (!$code) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Kode otorisasi tidak ditemukan.'];
    header('Location: login.php');
    exit;
}

$accessToken = getGoogleAccessToken($code);
if (!$accessToken) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal mendapatkan token dari Google.'];
    header('Location: login.php');
    exit;
}

// Ambil data user dari Google
$googleUser = getGoogleUserInfo($accessToken);
if (!$googleUser || empty($googleUser['email'])) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Gagal mengambil data profil Google.'];
    header('Location: login.php');
    exit;
}

$db = getDB();

// Cari user berdasarkan google_id
$stmt = $db->prepare('SELECT * FROM users WHERE google_id = ? LIMIT 1');
$stmt->execute([$googleUser['id']]);
$user = $stmt->fetch();

if ($user) {
    // User sudah ada dengan google_id → login langsung
    setUserSession($user);
    redirectByRole($user['role']);
    exit;
}

// Cari user berdasarkan email (mungkin sudah daftar manual)
$stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$googleUser['email']]);
$user = $stmt->fetch();

if ($user) {
    // Email sudah terdaftar → link dengan Google
    $upd = $db->prepare('UPDATE users SET google_id = ?, avatar = ?, auth_provider = "google" WHERE id = ?');
    $upd->execute([$googleUser['id'], $googleUser['picture'] ?? '', $user['id']]);
    $user['google_id'] = $googleUser['id'];
    $user['avatar'] = $googleUser['picture'] ?? '';
    $user['auth_provider'] = 'google';
    setUserSession($user);
    redirectByRole($user['role']);
    exit;
}

// User baru → register otomatis
$name  = $googleUser['name']  ?? explode('@', $googleUser['email'])[0];
$phone = '';
$hash  = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

$ins = $db->prepare('INSERT INTO users (name, email, phone, password, google_id, avatar, role, auth_provider) VALUES (?,?,?,?,?,?,?,?)');
$ins->execute([$name, $googleUser['email'], $phone, $hash, $googleUser['id'], $googleUser['picture'] ?? '', 'user', 'google']);

$newId = $db->lastInsertId();
$user = [
    'id'            => $newId,
    'name'          => $name,
    'email'         => $googleUser['email'],
    'phone'         => $phone,
    'password'      => $hash,
    'role'          => 'user',
    'google_id'     => $googleUser['id'],
    'avatar'        => $googleUser['picture'] ?? '',
    'auth_provider' => 'google',
];
setUserSession($user);
redirectByRole('user');
exit;

// ── Helper functions ──

function setUserSession(array $user): void {
    $_SESSION['user'] = [
        'id'            => $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'phone'         => $user['phone'],
        'role'          => $user['role'],
        'avatar'        => $user['avatar'] ?? '',
        'auth_provider' => $user['auth_provider'] ?? 'email',
    ];
    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => 'Login berhasil! Selamat datang, <strong>' . e($user['name']) . '</strong>.',
    ];
}

function redirectByRole(string $role): void {
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: kamar.php');
    }
}
