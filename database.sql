-- =============================================
--  APIK SINGGAH SINI — Database Schema
--  Jalankan file ini sekali di phpMyAdmin atau
--  MySQL CLI:  mysql -u root -p < database.sql
-- =============================================

CREATE DATABASE IF NOT EXISTS apik_kost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apik_kost;

-- ── Tabel settings ───────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
  setting_key   VARCHAR(100) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed: Default payment settings ────────────────
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('bca_number',    '1234567890'),
('bca_holder',    'Apik Singgah Sini'),
('bni_number',    '9876543210'),
('bni_holder',    'Apik Singgah Sini'),
('mandiri_number','1122334455'),
('mandiri_holder','Apik Singgah Sini'),
('qris_holder',   'Apik Singgah Sini'),
('qris_image',    'assets/images/payments/qr-qris.png');

-- ── Tabel users ──────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  phone      VARCHAR(20)  NOT NULL,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Tabel rooms ──────────────────────────────────
CREATE TABLE IF NOT EXISTS rooms (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  nomor                 VARCHAR(10)  NOT NULL UNIQUE,
  tipe                  VARCHAR(50)  NOT NULL,
  harga                 INT          NOT NULL,
  status                ENUM('kosong','booking','terisi') DEFAULT 'kosong',
  ukuran                VARCHAR(30),
  foto                  VARCHAR(255),
  fasilitas_kamar       TEXT,
  fasilitas_kamar_mandi TEXT,
  fasilitas_umum        TEXT,
  fasilitas_parkir      TEXT,
  deskripsi             TEXT,
  created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Tabel bookings ───────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  booking_code   VARCHAR(20)  NOT NULL UNIQUE,
  room_id        INT          NOT NULL,
  user_name      VARCHAR(100) NOT NULL,
  user_email     VARCHAR(150) NOT NULL,
  user_phone     VARCHAR(20)  NOT NULL,
  check_in_date  DATE         NOT NULL,
  duration       INT          NOT NULL DEFAULT 1,
  total          INT          NOT NULL,
  payment_method VARCHAR(20)  NOT NULL,
  note           TEXT,
  bukti_bayar    VARCHAR(255)          DEFAULT NULL,
  status         ENUM('menunggu_konfirmasi','diterima','ditolak') DEFAULT 'menunggu_konfirmasi',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB;

-- Jika tabel sudah ada (database lama), jalankan query ini sekali di phpMyAdmin:
-- ALTER TABLE bookings ADD COLUMN bukti_bayar VARCHAR(255) DEFAULT NULL AFTER note;

-- ── Seed: Admin default ───────────────────────────
INSERT IGNORE INTO users (name, email, phone, password, role)
VALUES ('Admin Kost', 'admin@kost.com', '628971022255',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- password: password

-- ── Seed: 5 kamar ─────────────────────────────────
INSERT IGNORE INTO rooms
  (nomor, tipe, harga, status, ukuran, foto, fasilitas_kamar, fasilitas_kamar_mandi, fasilitas_umum, fasilitas_parkir, deskripsi)
VALUES
('S1','Standar',1450000,'kosong','3x4 meter','assets/images/kamar-a1.jpg',
 'Kasur,Lemari,Bantal,Kipas Angin,Exhaust,Meja,Cermin,Kursi',
 'Kamar Mandi Luar,Shower,Kloset Duduk',
 'WiFi,Ruang Tamu,Ruang Jemuran,Dispenser,Kulkas,Penjaga Kos,Dapur,Pengurus Kos',
 'Parkiran Motor,Parkiran Mobil',
 'Kamar standar nyaman berukuran 3x4 meter. Kamar mandi bersama di luar. Cocok untuk mahasiswa atau pekerja yang butuh tempat tinggal praktis dan terjangkau.'),

('S2','Standar',1450000,'kosong','3x4 meter','assets/images/kamar-a2.jpg',
 'Kasur,Lemari,Bantal,Kipas Angin,Exhaust,Meja,Cermin,Kursi',
 'Kamar Mandi Luar,Shower,Kloset Duduk',
 'WiFi,Ruang Tamu,Ruang Jemuran,Dispenser,Kulkas,Penjaga Kos,Dapur,Pengurus Kos',
 'Parkiran Motor,Parkiran Mobil',
 'Kamar standar nyaman berukuran 3x4 meter. Kamar mandi bersama di luar. Cocok untuk mahasiswa atau pekerja yang butuh tempat tinggal praktis dan terjangkau.'),

('R1','Reguler',1600000,'kosong','3x6 meter','assets/images/kamar-b1.jpg',
 'Kasur,Lemari,Bantal,Kipas Angin,Exhaust,Meja,Cermin,Kursi',
 'Kamar Mandi Dalam,Air Panas,Shower,Kloset Duduk,Wastafel',
 'WiFi,Ruang Tamu,Ruang Jemuran,Dispenser,Kulkas,Penjaga Kos,Dapur,Pengurus Kos',
 'Parkiran Motor,Parkiran Mobil',
 'Kamar reguler luas berukuran 3x6 meter dengan kamar mandi dalam dan air panas. Lebih privat dan nyaman untuk kamu yang ingin ruang lebih lega.'),

('R2','Reguler',1600000,'kosong','3x6 meter','assets/images/kamar-b2.jpg',
 'Kasur,Lemari,Bantal,Kipas Angin,Exhaust,Meja,Cermin,Kursi',
 'Kamar Mandi Dalam,Air Panas,Shower,Kloset Duduk,Wastafel',
 'WiFi,Ruang Tamu,Ruang Jemuran,Dispenser,Kulkas,Penjaga Kos,Dapur,Pengurus Kos',
 'Parkiran Motor,Parkiran Mobil',
 'Kamar reguler luas berukuran 3x6 meter dengan kamar mandi dalam dan air panas. Lebih privat dan nyaman untuk kamu yang ingin ruang lebih lega.'),

('R3','Reguler',1600000,'kosong','3x6 meter','assets/images/kamar-b3.jpg',
 'Kasur,Lemari,Bantal,Kipas Angin,Exhaust,Meja,Cermin,Kursi',
 'Kamar Mandi Dalam,Air Panas,Shower,Kloset Duduk,Wastafel',
 'WiFi,Ruang Tamu,Ruang Jemuran,Dispenser,Kulkas,Penjaga Kos,Dapur,Pengurus Kos',
 'Parkiran Motor,Parkiran Mobil',
 'Kamar reguler luas berukuran 3x6 meter dengan kamar mandi dalam dan air panas. Lebih privat dan nyaman untuk kamu yang ingin ruang lebih lega.');
