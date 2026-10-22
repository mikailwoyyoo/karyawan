<?php
/**
 * Admin - Tambah Karyawan Baru
 */
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../api/fonnte.php';

$errors = [];
$nik = '';
$nama = '';
$jabatan = '';
$no_hp = '';
$email = '';
$role = 'karyawan';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'karyawan';
    $password = $_POST['password'] ?? '';

    // Validasi
    if (empty($nik) || empty($nama) || empty($jabatan) || empty($no_hp) || empty($email) || empty($password)) {
        $errors[] = 'Semua kolom wajib diisi.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    $normalizedPhone = normalizePhoneNumber($no_hp);
    if (!empty($no_hp) && (strlen($normalizedPhone) < 10 || strlen($normalizedPhone) > 16)) {
        $errors[] = 'Nomor WhatsApp tidak valid (minimal 10 digit).';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }

    if (empty($errors)) {
        $db = getDB();

        // Cek NIK
        $stmtNik = $db->prepare("SELECT id FROM karyawan WHERE nik = :nik LIMIT 1");
        $stmtNik->execute([':nik' => $nik]);
        if ($stmtNik->fetch()) {
            $errors[] = 'NIK sudah digunakan.';
        }

        // Cek Email
        $stmtEmail = $db->prepare("SELECT id FROM karyawan WHERE email = :email LIMIT 1");
        $stmtEmail->execute([':email' => $email]);
        if ($stmtEmail->fetch()) {
            $errors[] = 'Email sudah digunakan.';
        }
    }

    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db = getDB();

            $stmt = $db->prepare("INSERT INTO karyawan (nik, nama, jabatan, no_hp, email, password, role, created_at) VALUES (:nik, :nama, :jabatan, :no_hp, :email, :password, :role, NOW())");
            $saved = $stmt->execute([
                ':nik' => $nik,
                ':nama' => $nama,
                ':jabatan' => $jabatan,
                ':no_hp' => $normalizedPhone,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => in_array($role, ['admin', 'karyawan']) ? $role : 'karyawan'
            ]);

            if ($saved) {
                // Notifikasi WhatsApp
                $waEmployee = sendRegistrationNotificationEmployee($nama, $nik, $jabatan, $normalizedPhone);
                $waAdmin = sendRegistrationNotificationAdmin($nama, $nik, $jabatan, $normalizedPhone);

                $msg = 'Karyawan baru berhasil ditambahkan!';
                if ($waEmployee['success']) {
                    $msg .= ' Notifikasi WhatsApp terkirim ke karyawan.';
                } else {
                    $msg .= ' (Catatan WA: ' . h($waEmployee['message']) . ')';
                }

                set_flash('success', $msg);
                header('Location: ' . base_url('admin/karyawan.php'));
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Tambah Karyawan Baru';
$pageHeading = 'Tambah Karyawan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      <div style="margin-bottom: 20px;">
        <a href="<?= base_url('admin/karyawan.php') ?>" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-arrow-left"></i> Kembali ke Data Karyawan
        </a>
      </div>

      <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-header">
          <h3 class="card-title"><i class="fa-solid fa-user-plus text-accent"></i> Formulir Tambah Karyawan</h3>
        </div>

        <div class="card-body">
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <i class="fa-solid fa-circle-exclamation"></i>
              <div>
                <strong>Perhatian:</strong>
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
                <label class="form-label required">Nomor Induk Karyawan (NIK)</label>
                <input type="text" name="nik" class="form-control" placeholder="Contoh: KRY005" value="<?= h($nik) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label required">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Nama karyawan" value="<?= h($nama) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label required">Jabatan / Posisi</label>
                <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Staff Keuangan" value="<?= h($jabatan) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label required">Nomor WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 081234567890" value="<?= h($no_hp) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label required">Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="email@perusahaan.com" value="<?= h($email) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label required">Role Akses</label>
                <select name="role" class="form-control">
                  <option value="karyawan" <?= ($role === 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                  <option value="admin" <?= ($role === 'admin') ? 'selected' : '' ?>>Administrator</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label required">Password Akun</label>
              <div class="input-group has-icon-right">
                <input type="password" name="password" id="inputPass" class="form-control" placeholder="Minimal 6 karakter" required>
                <i class="fa-regular fa-eye input-icon-right toggle-password" data-target="inputPass"></i>
              </div>
            </div>

            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
              <a href="<?= base_url('admin/karyawan.php') ?>" class="btn btn-outline">Batal</a>
              <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Data & Kirim WA
              </button>
            </div>

          </form>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

