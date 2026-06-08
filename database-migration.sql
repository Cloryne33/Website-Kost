-- ===================================================
--  Migration: Tambah kolom Google OAuth ke tabel users
--  Jalankan di phpMyAdmin atau MySQL CLI setelah
--  database.sql
-- ===================================================

ALTER TABLE users
  ADD COLUMN google_id     VARCHAR(255) NULL UNIQUE AFTER email,
  ADD COLUMN avatar        VARCHAR(500) NULL AFTER google_id,
  ADD COLUMN auth_provider ENUM('email','google') DEFAULT 'email' AFTER role;
