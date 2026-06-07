# 🏠 Apik Singgah Sini — Website Kost PHP + MySQL

## Kebutuhan Server
- PHP 8.0+
- MySQL / MariaDB 5.7+
- Apache / Nginx dengan mod_rewrite aktif

---

## Cara Install

### 1. Copy file ke server
Upload seluruh folder `apik-php/` ke `htdocs` (XAMPP) atau `www` (Laragon/WAMP).

### 2. Buat database
Buka **phpMyAdmin** → pilih tab **SQL** → paste isi file `database.sql` → klik **Go**.

Atau via terminal:
```bash
mysql -u root -p < database.sql
```

### 3. Sesuaikan konfigurasi database
Edit file `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'apik_kost');
define('DB_USER', 'root');    // username MySQL kamu
define('DB_PASS', '');        // password MySQL kamu
```

### 4. Akses website
Buka browser: `http://localhost/apik-php/`

---

## Akun Default

| Role  | Email           | Password |
|-------|-----------------|----------|
| Admin | admin@kost.com  | password |

> Akun admin sudah otomatis dibuat saat menjalankan `database.sql`.

---

## Struktur Folder
```
apik-php/
├── index.php           # Beranda
├── kamar.php           # Daftar kamar
├── detail-kamar.php    # Detail + booking
├── login.php           # Login
├── register.php        # Register
├── logout.php          # Logout
├── database.sql        # Schema + seed data
├── .htaccess
├── admin/
│   └── dashboard.php   # Panel admin
├── assets/
│   ├── css/style.css
│   └── images/
└── includes/
    ├── config.php      # DB + helper functions
    ├── navbar.php
    └── footer.php
```

---

## Alur Booking

1. User login → pilih kamar → klik **Booking Sekarang**
2. Modal booking muncul:
   - **Step 1** — Isi data diri (nama, WA, email, tanggal masuk, durasi)
   - **Step 2** — Pilih metode pembayaran (BCA / BNI / Mandiri / QRIS)
   - **Step 3** — Lihat instruksi pembayaran + upload bukti transfer
3. Klik **Konfirmasi Booking** → data tersimpan ke MySQL, status kamar jadi `booking`
4. Admin buka dashboard → klik **Terima** (kamar jadi `terisi`) atau **Tolak** (kamar kembali `kosong`)

---

## Foto Kamar
Upload foto kamar ke folder `assets/images/`, lalu update kolom `foto` di tabel `rooms` via phpMyAdmin.
