<?php
/**
 * Admin - Edit Data Karyawan
 */
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../api/fonnte.php';

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM karyawan WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$karyawan = $stmt->fetch();

if (!$karyawan) {
    set_flash('error', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url('admin/karyawan.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jabatan = trim($_POST['jabatan'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'karyawan';
    $password = $_POST['password'] ?? '';

    if (empty($nik) || empty($nama) || empty($jabatan) || empty($no_hp) || empty($email)) {
        $errors[] = 'Semua kolom bertanda * wajib diisi.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    $normalizedPhone = normalizePhoneNumber($no_hp);
    if (!empty($no_hp) && (strlen($normalizedPhone) < 10 || strlen($normalizedPhone) > 16)) {
        $errors[] = 'Nomor WhatsApp tidak valid (minimal 10 digit).';
    }

    // Cek duplikasi NIK selain dirinya sendiri
    $stmtNik = $db->prepare("SELECT id FROM karyawan WHERE nik = :nik AND id != :id LIMIT 1");
    $stmtNik->execute([':nik' => $nik, ':id' => $id]);
    if ($stmtNik->fetch()) {
        $errors[] = 'NIK sudah digunakan oleh karyawan lain.';
    }

    // Cek duplikasi Email
    $stmtEmail = $db->prepare("SELECT id FROM karyawan WHERE email = :email AND id != :id LIMIT 1");
    $stmtEmail->execute([':email' => $email, ':id' => $id]);
    if ($stmtEmail->fetch()) {
        $errors[] = 'Email sudah digunakan oleh karyawan lain.';
    }

    if (empty($errors)) {
        try {
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $errors[] = 'Password baru minimal 6 karakter.';
                } else {
                    $newPassHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtUp = $db->prepare("UPDATE karyawan SET nik = :nik, nama = :nama, jabatan = :jabatan, no_hp = :hp, email = :email, role = :role, password = :pw, updated_at = NOW() WHERE id = :id");
                    $stmtUp->execute([
                        ':nik' => $nik,
                        ':nama' => $nama,
                        ':jabatan' => $jabatan,
                        ':hp' => $normalizedPhone,
                        ':email' => $email,
                        ':role' => in_array($role, ['admin', 'karyawan']) ? $role : 'karyawan',
                        ':pw' => $newPassHash,
                        ':id' => $id
                    ]);
                }
            } else {
                $stmtUp = $db->prepare("UPDATE karyawan SET nik = :nik, nama = :nama, jabatan = :jabatan, no_hp = :hp, email = :email, role = :role, updated_at = NOW() WHERE id = :id");
                $stmtUp->execute([
                    ':nik' => $nik,
                    ':nama' => $nama,
                    ':jabatan' => $jabatan,
                    ':hp' => $normalizedPhone,
                    ':email' => $email,
                    ':role' => in_array($role, ['admin', 'karyawan']) ? $role : 'karyawan',
                    ':id' => $id
                ]);
            }

            if (empty($errors)) {
                // Kirim notifikasi WhatsApp pembaruan data ke karyawan
                $waResult = sendUpdateNotificationEmployee($nama, $jabatan, $email, $normalizedPhone);

                $msg = 'Data karyawan berhasil diperbarui!';
                if ($waResult['success']) {
                    $msg .= ' Notifikasi WhatsApp perubahan telah dikirim ke karyawan.';
                } else {
                    $msg .= ' (Catatan WA: ' . h($waResult['message']) . ')';
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

$pageTitle = 'Edit Data Karyawan';
$pageHeading = 'Edit Karyawan';
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
          <h3 class="card-title"><i class="fa-solid fa-pen-to-square text-accent"></i> Edit Data: <?= h($karyawan['nama']) ?></h3>
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
                <input type="text" name="nik" class="form-control" value="<?= h($_POST['nik'] ?? $karyawan['nik']) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label required">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= h($_POST['nama'] ?? $karyawan['nama']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label required">Jabatan / Posisi</label>
                <input type="text" name="jabatan" class="form-control" value="<?= h($_POST['jabatan'] ?? $karyawan['jabatan']) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label required">Nomor WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" value="<?= h($_POST['no_hp'] ?? $karyawan['no_hp']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label required">Alamat Email</label>
                <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? $karyawan['email']) ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label required">Role Akses</label>
                <select name="role" class="form-control">
                  <option value="karyawan" <?= (($_POST['role'] ?? $karyawan['role']) === 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                  <option value="admin" <?= (($_POST['role'] ?? $karyawan['role']) === 'admin') ? 'selected' : '' ?>>Administrator</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Password Baru (Kosongkan jika tidak ingin diubah)</label>
              <div class="input-group has-icon-right">
                <input type="password" name="password" id="inputPass" class="form-control" placeholder="Biarkan kosong jika tidak diubah">
                <i class="fa-regular fa-eye input-icon-right toggle-password" data-target="inputPass"></i>
              </div>
            </div>

            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
              <a href="<?= base_url('admin/karyawan.php') ?>" class="btn btn-outline">Batal</a>
              <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan & Kirim Notif
              </button>
            </div>

          </form>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

