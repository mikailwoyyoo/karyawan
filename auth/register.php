<?php
/**
 * Halaman Registrasi Karyawan
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../api/fonnte.php';

// Jika sudah login, redirect
if (is_logged_in()) {
    header('Location: ' . base_url(is_admin() ? 'admin/index.php' : 'karyawan/index.php'));
    exit;
}

$errors = [];
$nik = '';
$nama = '';
$jabatan = '';
$no_hp = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    // Validasi Field Wajib
    if (empty($nik) || empty($nama) || empty($jabatan) || empty($no_hp) || empty($email) || empty($password)) {
        $errors[] = 'Semua kolom formulir wajib diisi.';
    }

    // Validasi NIK
    if (!empty($nik) && (strlen($nik) < 3 || strlen($nik) > 30)) {
        $errors[] = 'NIK harus terdiri dari 3 hingga 30 karakter.';
    }

    // Validasi Email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    // Validasi Nomor HP
    $normalizedPhone = normalizePhoneNumber($no_hp);
    if (!empty($no_hp) && (strlen($normalizedPhone) < 10 || strlen($normalizedPhone) > 16)) {
        $errors[] = 'Nomor WhatsApp tidak valid (minimal 10 digit, contoh: 081234567890).';
    }

    // Validasi Password
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi_password) {
        $errors[] = 'Konfirmasi password tidak cocok dengan password yang dimasukkan.';
    }

    // Validasi Keunikan NIK dan Email ke Database
    if (empty($errors)) {
        $db = getDB();
        
        // Cek NIK
        $stmt = $db->prepare("SELECT id FROM karyawan WHERE nik = :nik LIMIT 1");
        $stmt->execute([':nik' => $nik]);
        if ($stmt->fetch()) {
            $errors[] = 'NIK sudah terdaftar dalam sistem. Gunakan NIK lain.';
        }

        // Cek Email
        $stmt = $db->prepare("SELECT id FROM karyawan WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah digunakan. Gunakan email lain atau silakan login.';
        }
    }

    // Jika seluruh validasi lolos, simpan ke database
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db = getDB();

            $stmt = $db->prepare("INSERT INTO karyawan (nik, nama, jabatan, no_hp, email, password, role, created_at) VALUES (:nik, :nama, :jabatan, :no_hp, :email, :password, 'karyawan', NOW())");
            $saved = $stmt->execute([
                ':nik' => $nik,
                ':nama' => $nama,
                ':jabatan' => $jabatan,
                ':no_hp' => $normalizedPhone,
                ':email' => $email,
                ':password' => $hashedPassword
            ]);

            if ($saved) {
                // 1. Kirim Notifikasi WhatsApp ke Karyawan
                $waKaryawanResult = sendRegistrationNotificationEmployee($nama, $nik, $jabatan, $normalizedPhone);

                // 2. Kirim Notifikasi WhatsApp ke Admin
                $waAdminResult = sendRegistrationNotificationAdmin($nama, $nik, $jabatan, $normalizedPhone);

                $msg = 'Registrasi berhasil! Akun Anda telah aktif.';
                if ($waKaryawanResult['success']) {
                    $msg .= ' Notifikasi konfirmasi telah dikirimkan ke nomor WhatsApp Anda.';
                } else {
                    $msg .= ' (Catatan: Notifikasi WhatsApp ' . h($waKaryawanResult['message']) . ')';
                }

                set_flash('success', $msg);
                header('Location: ' . base_url('auth/login.php'));
                exit;
            } else {
                $errors[] = 'Terjadi kesalahan sistem saat menyimpan data.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Gagal menyimpan pendaftaran: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Registrasi Akun Karyawan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card register-card">
    <div class="auth-header">
      <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo" class="auth-logo">
      <h3 class="auth-title">Registrasi Karyawan Baru</h3>
      <p class="auth-subtitle">Lengkapi formulir berikut untuk membuat akun absensi karyawan</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
          <strong>Mohon perbaiki kesalahan berikut:</strong>
          <ul style="margin-left: 18px; margin-top: 4px;">
            <?php foreach ($errors as $err): ?>
              <li><?= h($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= csrf_field() ?>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="nik">Nomor Induk Karyawan (NIK)</label>
          <div class="input-group has-icon">
            <i class="fa-solid fa-id-card input-icon"></i>
            <input type="text" name="nik" id="nik" class="form-control" placeholder="Contoh: KRY004" value="<?= h($nik) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required" for="nama">Nama Lengkap</label>
          <div class="input-group has-icon">
            <i class="fa-solid fa-user input-icon"></i>
            <input type="text" name="nama" id="nama" class="form-control" placeholder="Nama lengkap Anda" value="<?= h($nama) ?>" required>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="jabatan">Jabatan / Posisi</label>
          <div class="input-group has-icon">
            <i class="fa-solid fa-briefcase input-icon"></i>
            <input type="text" name="jabatan" id="jabatan" class="form-control" placeholder="Contoh: Staff IT, Marketing" value="<?= h($jabatan) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required" for="no_hp">Nomor WhatsApp</label>
          <div class="input-group has-icon">
            <i class="fa-brands fa-whatsapp input-icon"></i>
            <input type="text" name="no_hp" id="no_hp" class="form-control" placeholder="Contoh: 081234567890" value="<?= h($no_hp) ?>" required>
          </div>
          <small class="form-text">Notifikasi pendaftaran & absensi akan dikirim ke nomor ini.</small>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label required" for="email">Alamat Email</label>
        <div class="input-group has-icon">
          <i class="fa-regular fa-envelope input-icon"></i>
          <input type="email" name="email" id="email" class="form-control" placeholder="nama@perusahaan.com" value="<?= h($email) ?>" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label required" for="password">Kata Sandi (Password)</label>
          <div class="input-group has-icon has-icon-right">
            <i class="fa-solid fa-lock input-icon"></i>
            <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 karakter" required>
            <i class="fa-regular fa-eye input-icon-right toggle-password" data-target="password"></i>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label required" for="konfirmasi_password">Konfirmasi Sandi</label>
          <div class="input-group has-icon has-icon-right">
            <i class="fa-solid fa-lock-check input-icon"></i>
            <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" placeholder="Ulangi password" required>
            <i class="fa-regular fa-eye input-icon-right toggle-password" data-target="konfirmasi_password"></i>
          </div>
        </div>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn btn-accent btn-block btn-lg">
          <i class="fa-solid fa-user-plus"></i>
          <span>Daftar Sekarang</span>
        </button>
      </div>
    </form>

    <div class="auth-footer">
      Sudah memiliki akun? <a href="<?= base_url('auth/login.php') ?>">Masuk di sini</a>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/js/script.js') ?>"></script>
</body>
</html>

