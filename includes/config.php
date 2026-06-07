<?php
// ── Konfigurasi Database ──────────────────────────
// Sesuaikan dengan setting server kamu
define('DB_HOST', 'localhost');
define('DB_NAME', 'apik_kost');
define('DB_USER', 'root');       // ganti dengan username MySQL kamu
define('DB_PASS', '');           // ganti dengan password MySQL kamu
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Apik Singgah Sini');
define('SITE_URL',  '');         // kosongkan jika di root, atau isi: '/apik-kost'

// ── Service Center ──────────────────────────
define('WA_NUMBER', '6281234567890');  // ganti dengan nomor WA admin
define('WA_URL',    'https://wa.me/' . WA_NUMBER);

// ── Koneksi PDO ───────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// ── Session helper ────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function getLoggedInUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function requireLogin(string $redirect = '../login.php'): array {
    $user = getLoggedInUser();
    if (!$user) {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}

function requireAdmin(string $redirect = '../index.php'): array {
    $user = requireLogin('../login.php');
    if ($user['role'] !== 'admin') {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}

// ── Helpers ───────────────────────────────────────
function formatRupiah(int $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function generateBookingCode(): string {
    return 'APK-' . strtoupper(substr(uniqid(), -8));
}

function parseFacility(?string $csv): array {
    if (!$csv) return [];
    return array_map('trim', explode(',', $csv));
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function statusBadge(string $status): string {
    $map = [
        'kosong'               => ['class' => 'status-kosong',  'label' => 'Tersedia'],
        'booking'              => ['class' => 'status-booking', 'label' => 'Sedang Dipesan'],
        'terisi'               => ['class' => 'status-terisi',  'label' => 'Terisi'],
        'menunggu_konfirmasi'  => ['class' => 'status-booking', 'label' => 'Menunggu Konfirmasi'],
        'diterima'             => ['class' => 'status-kosong',  'label' => 'Diterima ✓'],
        'ditolak'              => ['class' => 'status-full',    'label' => 'Ditolak'],
    ];
    $s = $map[$status] ?? ['class' => 'status-full', 'label' => $status];
    return '<span class="status ' . $s['class'] . '">' . $s['label'] . '</span>';
}

function jsonResponse(mixed $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
