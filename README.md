# KERJOO - Sistem Informasi Manajemen Karyawan & Absensi Berbasis Web dengan Integrasi WhatsApp Gateway Fonnte

**KERJOO** adalah aplikasi sistem informasi Human Capital (HC) modern berbasis Web yang mengintegrasikan pencatatan kehadiran karyawan (absensi masuk dan pulang), manajemen data karyawan (CRUD), dashboard analitik, laporan rekapitulasi, dan **pengiriman notifikasi WhatsApp otomatis secara real-time via WhatsApp Gateway Fonnte API**.

Dibangun menggunakan **PHP Native** berstandar keamanan tinggi (PDO Prepared Statements, CSRF Protection, Password Hashing) dan basis data **MySQL/MariaDB**, serta didesain responsif untuk perangkat mobile maupun desktop dengan inspirasi desain UI HR modern.

---

## 📱 Fitur Utama Aplikasi

### 1. Autentikasi Multi-Role & Keamanan
- **Role Administrator**: Akses penuh ke seluruh data karyawan, riwayat absensi, rekapitulasi laporan, konfigurasi jam kerja kantor, log WhatsApp, serta pengujian gateway Fonnte.
- **Role Karyawan**: Halaman personal berbasis mobile-first, check-in masuk & check-out pulang real-time, pantauan jadwal kerja, serta riwayat absensi bulanan.
- **Keamanan Ketat**:
  - `password_hash()` dan `password_verify()` dengan algoritma BCRYPT.
  - Prepared statements PDO untuk pencegahan SQL Injection.
  - Proteksi Cross-Site Request Forgery (CSRF) token di seluruh formulir aksi.
  - Sanitasi input dan proteksi Cross-Site Scripting (XSS).
  - Pembatasan hak akses berbasis role (Admin vs Karyawan) dengan session guards.

### 2. Integrasi WhatsApp Gateway Fonnte API
- Modul mandiri `api/fonnte.php` dengan fungsi `sendWhatsApp($nomor, $pesan)`.
- Normalisasi nomor telepon otomatis ke format standar internasional (`628xxx`).
- Notifikasi otomatis pada peristiwa berikut:
  1. **Registrasi Karyawan**: Notifikasi konfirmasi akun ke karyawan & pemberitahuan pendaftaran baru ke admin.
  2. **Absensi Masuk (Check-In)**: Notifikasi jam masuk dan status kehadiran (Hadir / Terlambat).
  3. **Absensi Pulang (Check-Out)**: Notifikasi jam pulang dan jam masuk terangkum.
  4. **Pembaruan Data Karyawan**: Notifikasi perubahan identitas kerja.
  5. **Penghapusan Karyawan**: Notifikasi pemberitahuan pencabutan data.
- **Non-blocking & Error Resilient**: Kegagalan koneksi atau limitasi API WhatsApp tidak akan membatalkan transaksi database pendaftaran maupun absensi.
- **Tabel Log WhatsApp**: Seluruh aktivitas pengiriman, nomor tujuan, status pesan (`sent` / `failed`), dan respons API tercatat di database tanpa mengekspos token rahasia.

### 3. Presensi & Absensi Karyawan (Manual & Barcode/QR Code)
- Validasi waktu berbasis **jam server** (bukan jam perangkat klien).
- Pengaturan jam masuk, toleransi keterlambatan, dan jam pulang yang dapat dikonfigurasi langsung dari dashboard admin.
- Proteksi pencegahan absensi ganda (karyawan tidak dapat absen masuk 2 kali dalam 1 hari).
- Proteksi absensi pulang (harus sudah absen masuk dan tidak dapat absen pulang 2 kali).
- **Barcode & QR Code Digital Karyawan**: Karyawan dapat memunculkan kartu barcode/QR code presensi pribadi di layar HP mereka (di Dashboard maupun menu Absen).
- **Kamera Scanner Admin (`admin/scan.php`)**: Admin dapat memindai barcode/QR code karyawan langsung via webcam / kamera smartphone secara live, atau menggunakan alat pemindai laser USB Barcode Scanner.
- **Audio Beep & Live Feedback**: Dilengkapi efek suara beep instan saat barcode berhasil dipindai dan langsung menembak notifikasi WhatsApp ke smartphone karyawan.

### 4. Dashboard & Laporan Analitik
- Dashboard Admin dengan 6 kartu statistik real-time: Total Karyawan, Sudah Masuk, Belum Masuk, Sudah Pulang, Terlambat, dan Total Absensi Hari Ini.
- Grafik visual interaktif 7 hari terakhir menggunakan **Chart.js**.
- Laporan rekapitulasi dengan filter rentang tanggal, filter per karyawan, dan filter status kehadiran.
- Ekspor laporan langsung ke format **Excel (.CSV UTF-8)** dan Cetak / PDF.

---

## 📂 Struktur Folder Proyek

```
karyawan/
├── admin/                     # Modul Antarmuka Administrator
│   ├── index.php              # Dashboard analitik & grafik Chart.js
│   ├── scan.php               # Scanner kamera Barcode & QR Code absensi
│   ├── karyawan.php           # Tabel CRUD data karyawan & pencarian
│   ├── tambah_karyawan.php    # Form tambah karyawan + trigger WA
│   ├── edit_karyawan.php      # Form edit karyawan + trigger WA
│   ├── hapus_karyawan.php     # Logika hapus karyawan aman + trigger WA
│   ├── absensi.php            # Monitoring seluruh absensi harian
│   ├── laporan.php            # Rekapitulasi laporan kehadiran + filter
│   ├── export_excel.php       # Ekspor laporan ke Microsoft Excel
│   ├── pengaturan.php         # Konfigurasi jam kantor, token Fonnte & live test
│   └── wa_logs.php            # Audit log riwayat pengiriman WhatsApp
├── api/
│   ├── fonnte.php             # Library WhatsApp Gateway API Fonnte
│   └── scan_process.php       # Endpoint AJAX pemrosesan scan barcode live
├── assets/
│   ├── css/
│   │   └── style.css          # Desain modern, responsive mobile & desktop
│   ├── js/
│   │   └── script.js          # Jam digital live, modal dialog, toggle password
│   └── img/
│       ├── logo.svg           # Logo aplikasi HC Pro
│       ├── favicon.svg        # Favicon browser identik logo
│       └── illustration.svg   # Ilustrasi halaman autentikasi
├── auth/
│   ├── login.php              # Halaman login (NIK / Email)
│   ├── register.php           # Halaman registrasi karyawan baru
│   └── logout.php             # Halaman logout & pembersihan sesi
├── absensi/
│   ├── index.php              # Halaman absensi presensi harian
│   ├── masuk.php              # Endpoint proses check-in absensi masuk
│   ├── pulang.php             # Endpoint proses check-out absensi pulang
│   └── riwayat.php            # Riwayat absensi personal karyawan
├── config/
│   ├── database.php           # Koneksi PDO MySQL & penanganan error
│   └── config.php             # Helper global, session, CSRF & base_url
├── includes/
│   ├── auth_check.php         # Guard pengecekan login pengguna
│   ├── admin_check.php        # Guard proteksi role khusus administrator
│   ├── header.php             # Layout header & link aset
│   ├── sidebar.php            # Navigasi sidebar admin
│   ├── navbar.php             # Top navbar admin dengan jam server
│   └── footer.php             # Layout footer & modal hapus global
├── karyawan/
│   ├── index.php              # Mobile-first dashboard karyawan
│   ├── detail.php             # Halaman detail profil & riwayat karyawan
│   └── profil.php             # Halaman pembaruan profil & kata sandi
├── database.sql               # Skrip pembuatan database, relasi & data awal
├── .env.example               # Contoh konfigurasi environment
├── .gitignore
├── index.php                  # Router utama aplikasi
└── README.md                  # Dokumentasi & panduan instalasi
```

---

## 🛠️ Panduan Instalasi di Windows (XAMPP)

### Langkah 1: Penempatan Berkas
Pastikan seluruh file proyek berada pada direktori web root XAMPP Anda:
- Contoh: `C:\xampp\htdocs\karyawan\` (atau `C:\xampp2\htdocs\karyawan\`)

### Langkah 2: Menjalankan Server XAMPP
1. Buka **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**.
3. Pastikan kedua modul berstatus hijau (*Running*).

### Langkah 3: Impor Basis Data MySQL
1. Buka peramban (browser) dan akses: `http://localhost/phpmyadmin/`.
2. Klik tab **Import** (atau buat database baru bernama `db_karyawan`).
3. Pilih file `database.sql` yang berada di dalam folder proyek ini.
4. Klik tombol **Go / Kirim** di bagian bawah. Seluruh tabel (`karyawan`, `absensi`, `pengaturan`, `wa_logs`) beserta data awal akan otomatis terbuat.

### Langkah 4: Verifikasi Konfigurasi Database
Buka file `config/database.php` dan pastikan pengaturan sesuai dengan lingkungan lokal Anda (default XAMPP tanpa password):
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'db_karyawan');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Langkah 5: Mengakses Aplikasi di Browser
Buka browser dan kunjungi:
👉 **`http://localhost/karyawan/`**

---

## 🔑 Akun Demo Pengujian

Aplikasi telah dilengkapi akun bawaan untuk kemudahan pengujian jobsheet praktikum:

| Role | Akun Login (NIK / Email) | Password |
|---|---|---|
| **Administrator** | `admin@karyawan.com` (atau NIK: `ADM001`) | `admin123` |
| **Karyawan 1** | `vera@karyawan.com` (atau NIK: `KRY001`) | `karyawan123` |
| **Karyawan 2** | `andhito@karyawan.com` (atau NIK: `KRY002`) | `karyawan123` |

---

## 📲 Panduan Menghubungkan WhatsApp Gateway Fonnte

Untuk mengaktifkan pengiriman notifikasi WhatsApp ke smartphone Anda:
1. Kunjungi [https://fonnte.com](https://fonnte.com) dan buat akun gratis.
2. Pada menu **Device**, klik **Add Device** lalu scan QR Code menggunakan aplikasi WhatsApp di smartphone Anda.
3. Setelah perangkat berstatus *Connected*, buka tab **API** pada dashboard Fonnte lalu salin **API Token**.
4. Login ke aplikasi ini sebagai Admin (`admin@karyawan.com`), buka menu **Pengaturan & WA** (`admin/pengaturan.php`).
5. Tempelkan token pada kolom **API Token Fonnte** dan isi nomor WhatsApp admin penerima notifikasi, lalu klik **Simpan Konfigurasi**.
6. Gunakan formulir **Uji Coba Pengiriman WhatsApp** pada halaman yang sama untuk mengetes pengiriman pesan secara langsung.

---

## ❓ Pemecahan Masalah (Troubleshooting)

- **Gagal Terhubung ke Database MySQL**:
  Pastikan service MySQL di XAMPP berstatus aktif (Port 3306). Jika port MySQL Anda berbeda (misal 3307), sesuaikan konstanta `DB_PORT` di `config/database.php`.
- **Pesan WhatsApp Gagal Terkirim**:
  Pastikan device WhatsApp di Fonnte berstatus aktif / *Connected*. Cek menu **WhatsApp Logs** pada dashboard admin untuk melihat pesan error spesifik yang dikembalikan oleh server Fonnte.
- **Validasi Keamanan Gagal (CSRF Mismatch)**:
  Terjadi apabila sesi browser telah kadaluarsa. Silakan refresh halaman dan ulangi proses pengiriman form.

