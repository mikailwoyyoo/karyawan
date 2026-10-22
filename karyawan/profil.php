<?php
/**
 * Halaman Profil Karyawan
 */
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$db = getDB();

$error = '';
$success = '';

// Ambil data terbaru dari database
$stmt = $db->prepare("SELECT * FROM karyawan WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $user['id']]);
$userData = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $nama = trim($_POST['nama'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama) || empty($no_hp) || empty($email)) {
            $error = 'Nama, Nomor WhatsApp, dan Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Cek keunikan email jika berubah
            $stmtCheck = $db->prepare("SELECT id FROM karyawan WHERE email = :email AND id != :id LIMIT 1");
            $stmtCheck->execute([':email' => $email, ':id' => $user['id']]);
            if ($stmtCheck->fetch()) {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                $cleanPhone = preg_replace('/[^0-9]/', '', $no_hp);
                if (str_starts_with($cleanPhone, '0')) $cleanPhone = '62' . substr($cleanPhone, 1);

                $stmtUp = $db->prepare("UPDATE karyawan SET nama = :nama, no_hp = :hp, email = :email, updated_at = NOW() WHERE id = :id");
                $stmtUp->execute([
                    ':nama' => $nama,
                    ':hp' => $cleanPhone,
                    ':email' => $email,
                    ':id' => $user['id']
                ]);

                // Update session
                $_SESSION['user_nama'] = $nama;
                $_SESSION['user_no_hp'] = $cleanPhone;
                $_SESSION['user_email'] = $email;

                $success = 'Data profil Anda berhasil diperbarui!';
                // Refresh data
                $userData['nama'] = $nama;
                $userData['no_hp'] = $cleanPhone;
                $userData['email'] = $email;
            }
        }
    } elseif ($action === 'change_password') {
        $passLama = $_POST['password_lama'] ?? '';
        $passBaru = $_POST['password_baru'] ?? '';
        $passKonf = $_POST['password_konfirmasi'] ?? '';

        if (empty($passLama) || empty($passBaru) || empty($passKonf)) {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (!password_verify($passLama, $userData['password'])) {
            $error = 'Password saat ini tidak sesuai.';
        } elseif (strlen($passBaru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($passBaru !== $passKonf) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $hashed = password_hash($passBaru, PASSWORD_DEFAULT);
            $stmtPass = $db->prepare("UPDATE karyawan SET password = :pw WHERE id = :id");
            $stmtPass->execute([':pw' => $hashed, ':id' => $user['id']]);
            $success = 'Password Anda berhasil diperbarui!';
        }
    }
}

$pageTitle = 'Profil Pengguna';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mobile-app-container">
  
  <div class="mobile-header" style="padding-bottom: 55px;">
    <div class="mobile-header-top">
      <div>
        <a href="<?= base_url('karyawan/index.php') ?>" style="color: #93c5fd; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
          <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <h2 class="mobile-user-name">Profil Saya</h2>
        <div class="mobile-user-role"><?= h($userData['jabatan']) ?></div>
      </div>
      <div class="mobile-user-avatar">
        <?= strtoupper(substr($userData['nama'], 0, 1)) ?>
      </div>
    </div>
  </div>

  <div style="padding: 0 20px; margin-top: -30px; position: relative; z-index: 10;">
    
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= h($error) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= h($success) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <!-- ID Card Badge Info -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-body" style="padding: 20px;">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
          <div style="width: 50px; height: 50px; border-radius: 14px; background: linear-gradient(135deg, #0b2863, #4f46e5); color:#fff; display:flex; align-items:center; justify-content:center; font-size: 20px; font-weight:700;">
            <?= strtoupper(substr($userData['nama'], 0, 1)) ?>
          </div>
          <div>
            <h4 style="font-size: 16px; margin-bottom: 2px;"><?= h($userData['nama']) ?></h4>
            <div style="font-size: 12px; color: var(--text-muted);"><?= h($userData['jabatan']) ?></div>
          </div>
          <span class="badge badge-info" style="margin-left: auto;">
            <?= strtoupper($userData['role']) ?>
          </span>
        </div>

        <div style="font-size: 12.5px; border-top: 1px solid var(--border-color); padding-top: 12px; display: grid; gap: 8px;">
          <div><strong style="color:var(--text-muted);">NIK:</strong> <?= h($userData['nik']) ?></div>
          <div><strong style="color:var(--text-muted);">Email:</strong> <?= h($userData['email']) ?></div>
          <div><strong style="color:var(--text-muted);">WhatsApp:</strong> +<?= h($userData['no_hp']) ?></div>
          <div><strong style="color:var(--text-muted);">Terdaftar:</strong> <?= format_indo_date($userData['created_at']) ?></div>
        </div>
      </div>
    </div>

    <!-- Edit Profil Form -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 14px;"><i class="fa-solid fa-user-pen text-accent"></i> Perbarui Data Profil</h4>
      </div>
      <div class="card-body" style="padding: 20px;">
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" value="<?= h($userData['nama']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Nomor WhatsApp</label>
            <input type="text" name="no_hp" class="form-control" value="<?= h($userData['no_hp']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= h($userData['email']) ?>" required>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan Profil
          </button>
        </form>
      </div>
    </div>

    <!-- Ganti Password Form -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 14px;"><i class="fa-solid fa-key text-accent"></i> Ganti Password Akun</h4>
      </div>
      <div class="card-body" style="padding: 20px;">
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">

          <div class="form-group">
            <label class="form-label">Password Saat Ini</label>
            <input type="password" name="password_lama" class="form-control" placeholder="Masukkan password lama" required>
          </div>

          <div class="form-group">
            <label class="form-label">Password Baru</label>
            <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter" required>
          </div>

          <div class="form-group">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" name="password_konfirmasi" class="form-control" placeholder="Ulangi password baru" required>
          </div>

          <button type="submit" class="btn btn-outline btn-block">
            <i class="fa-solid fa-lock"></i> Perbarui Password
          </button>
        </form>
      </div>
    </div>

  </div>

  <!-- Bottom Navbar -->
  <nav class="bottom-navbar">
    <a href="<?= base_url('karyawan/index.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </a>
    <a href="<?= base_url('absensi/index.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-calendar-check"></i>
      <span>Absen</span>
    </a>
    <a href="<?= base_url('absensi/riwayat.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <span>Riwayat</span>
    </a>
    <a href="<?= base_url('karyawan/profil.php') ?>" class="bottom-nav-item active">
      <i class="fa-solid fa-user"></i>
      <span>Profile</span>
    </a>
  </nav>

</div>

<script src="<?= base_url('assets/js/script.js') ?>"></script>
</body>
</html>

