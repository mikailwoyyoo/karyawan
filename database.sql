-- ========================================================
-- SISTEM INFORMASI MANAJEMEN KARYAWAN & ABSENSI WHATSAPP
-- Database: db_karyawan
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_karyawan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_karyawan`;

-- --------------------------------------------------------
-- Table structure for table `karyawan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `karyawan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nik` VARCHAR(30) NOT NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `jabatan` VARCHAR(100) NOT NULL,
  `no_hp` VARCHAR(25) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_karyawan_role` (`role`),
  INDEX `idx_karyawan_nik` (`nik`),
  INDEX `idx_karyawan_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `absensi`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `absensi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_karyawan` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_masuk` TIME DEFAULT NULL,
  `jam_pulang` TIME DEFAULT NULL,
  `status` ENUM('Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpha') NOT NULL DEFAULT 'Hadir',
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_karyawan_tanggal` (`id_karyawan`, `tanggal`),
  INDEX `idx_absensi_tanggal` (`tanggal`),
  INDEX `idx_absensi_status` (`status`),
  CONSTRAINT `fk_absensi_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `pengaturan`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_pengaturan` VARCHAR(50) NOT NULL UNIQUE,
  `nilai` TEXT NOT NULL,
  `deskripsi` VARCHAR(255) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `wa_logs`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wa_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_tujuan` VARCHAR(25) NOT NULL,
  `nama_penerima` VARCHAR(100) DEFAULT NULL,
  `pesan` TEXT NOT NULL,
  `tipe_notif` VARCHAR(50) NOT NULL,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'failed',
  `response_api` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_walogs_status` (`status`),
  INDEX `idx_walogs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Default Settings Data
-- --------------------------------------------------------
INSERT INTO `pengaturan` (`nama_pengaturan`, `nilai`, `deskripsi`) VALUES
('nama_aplikasi', 'KERJOO', 'Nama resmi website/aplikasi'),
('jam_masuk_normal', '08:00:00', 'Batas jam masuk kantor normal'),
('jam_masuk_toleransi', '08:15:00', 'Batas toleransi kehadiran, lewat dari ini dianggap Terlambat'),
('jam_pulang_normal', '17:00:00', 'Jam kepulangan kantor standar'),
('fonnte_token', '', 'API Token WhatsApp Gateway dari Fonnte.com'),
('admin_whatsapp', '6281234567890', 'Nomor WhatsApp admin penerima notifikasi pendaftaran'),
('wa_notif_aktif', '1', 'Status aktifasi notifikasi WhatsApp (1 = Aktif, 0 = Nonaktif)')
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

-- --------------------------------------------------------
-- Seed Users (Password: admin123 untuk admin, karyawan123 untuk karyawan)
-- --------------------------------------------------------
INSERT INTO `karyawan` (`id`, `nik`, `nama`, `jabatan`, `no_hp`, `email`, `password`, `role`) VALUES
(1, 'ADM001', 'Administrator Sistem', 'Human Resource Manager', '6281234567890', 'admin@karyawan.com', '$2y$10$1lKeI74VdD.yWfIY8UkMte5D4/aKkv2Xr2zhs9A9W6qvmKXOM61yO', 'admin'),
(2, 'KRY001', 'Vera Angelina', 'Business Process Development', '6281298765432', 'vera@karyawan.com', '$2y$10$SGAoL24MYZxU.JbedSqOT.CYX8mNojcI.D0LHzffUMbAOkGiFDhu.', 'karyawan'),
(3, 'KRY002', 'Andhito Diaz', 'UI/UX Designer', '6281311223344', 'andhito@karyawan.com', '$2y$10$SGAoL24MYZxU.JbedSqOT.CYX8mNojcI.D0LHzffUMbAOkGiFDhu.', 'karyawan'),
(4, 'KRY003', 'Budi Santoso', 'Fullstack Web Developer', '6285788990011', 'budi@karyawan.com', '$2y$10$SGAoL24MYZxU.JbedSqOT.CYX8mNojcI.D0LHzffUMbAOkGiFDhu.', 'karyawan')
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

-- --------------------------------------------------------
-- Seed Attendance Samples (Current & Previous Dates for Charts & Reports)
-- --------------------------------------------------------
INSERT INTO `absensi` (`id_karyawan`, `tanggal`, `jam_masuk`, `jam_pulang`, `status`, `keterangan`) VALUES
(2, CURDATE() - INTERVAL 2 DAY, '08:05:00', '17:02:00', 'Hadir', 'Tepat waktu'),
(3, CURDATE() - INTERVAL 2 DAY, '08:25:00', '17:15:00', 'Terlambat', 'Terjebak macet'),
(4, CURDATE() - INTERVAL 2 DAY, '07:55:00', '17:00:00', 'Hadir', 'Tepat waktu'),
(2, CURDATE() - INTERVAL 1 DAY, '08:02:00', '17:05:00', 'Hadir', 'Tepat waktu'),
(3, CURDATE() - INTERVAL 1 DAY, '08:08:00', '17:00:00', 'Hadir', 'Tepat waktu'),
(4, CURDATE() - INTERVAL 1 DAY, NULL, NULL, 'Sakit', 'Surat dokter terlampir'),
(2, CURDATE(), '08:01:00', NULL, 'Hadir', 'Sudah absen masuk'),
(3, CURDATE(), '08:30:00', NULL, 'Terlambat', 'Keterlambatan 15 menit')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

