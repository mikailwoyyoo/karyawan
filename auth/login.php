<?php
/**
 * Halaman Login Sistem
 */
require_once __DIR__ . '/../config/config.php';

// Jika sudah login, redirect sesuai role
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . base_url('admin/index.php'));
    } else {
        header('Location: ' . base_url('karyawan/index.php'));
    }
    exit;
}

$error = '';
$usernameVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $usernameVal = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usernameVal) || empty($password)) {
        $error = 'Silakan isi NIK/Email dan Password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM karyawan WHERE nik = :u1 OR email = :u2 LIMIT 1");
        $stmt->execute([':u1' => $usernameVal, ':u2' => $usernameVal]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Sesi login berhasil
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nik'] = $user['nik'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_jabatan'] = $user['jabatan'];
            $_SESSION['user_no_hp'] = $user['no_hp'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];

            set_flash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

            if ($user['role'] === 'admin') {
                header('Location: ' . base_url('admin/index.php'));
            } else {
                header('Location: ' . base_url('karyawan/index.php'));
            }
            exit;
        } else {
            $error = 'NIK/Email atau Password yang Anda masukkan tidak sesuai.';
        }
    }
}

$pageTitle = 'Login Pengguna';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <img src="<?= base_url('assets/img/illustration.svg') ?>" alt="Login Illustration" class="auth-illustration">
      <div style="font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 2px;">Selamat Datang di</div>
      <h3 style="font-size: 22px; font-weight: 700; color: var(--primary-dark); margin-bottom: 12px; letter-spacing: -0.5px;"><?= h(get_setting('nama_aplikasi', 'KERJOO')) ?></h3>
      <h4 style="font-size: 16px; font-weight: 600; text-align: left; margin-top: 8px;">Masuk ke Akun</h4>
    </div>

    <?php if ($flashErr = get_flash('error')): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= h($flashErr) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= h($error) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($flashSuccess = get_flash('success')): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= h($flashSuccess) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="username">NIK atau Email</label>
        <div class="input-group has-icon">
          <i class="fa-regular fa-user input-icon"></i>
          <input type="text" name="username" id="username" class="form-control" placeholder="Contoh: KRY001 atau vera@karyawan.com" value="<?= h($usernameVal) ?>" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Kata Sandi / Password</label>
        <div class="input-group has-icon has-icon-right">
          <i class="fa-solid fa-lock input-icon"></i>
          <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
          <i class="fa-regular fa-eye input-icon-right toggle-password" data-target="password" title="Lihat password"></i>
        </div>
      </div>

      <div style="margin-top: 24px;">
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="border-radius: var(--radius-md);">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          <span>Masuk Sekarang</span>
        </button>
      </div>
    </form>

    <div class="auth-footer">
      Belum memiliki akun karyawan? <a href="<?= base_url('auth/register.php') ?>">Daftar di sini</a>
    </div>

    <!-- Info Akun Demo Praktikum -->
    <div style="margin-top: 20px; padding: 12px; background: #f8fafc; border: 1px dashed var(--border-color); border-radius: var(--radius-sm); font-size: 11px; color: var(--text-muted);">
      <strong>Akun Demo Praktikum:</strong><br>
      • Admin: <code>admin@karyawan.com</code> | Pass: <code>admin123</code><br>
      • Karyawan: <code>vera@karyawan.com</code> | Pass: <code>karyawan123</code>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/js/script.js') ?>"></script>
</body>
</html>

