<?php
/**
 * Admin - Pengaturan Sistem & Integrasi WhatsApp Gateway Fonnte
 */
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../api/fonnte.php';

$error = '';
$success = '';
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $namaApp = trim($_POST['nama_aplikasi'] ?? 'KERJOO');
        $jamMasuk = trim($_POST['jam_masuk_normal'] ?? '08:00:00');
        $jamToleransi = trim($_POST['jam_masuk_toleransi'] ?? '08:15:00');
        $jamPulang = trim($_POST['jam_pulang_normal'] ?? '17:00:00');
        $fonnteToken = trim($_POST['fonnte_token'] ?? '');
        $adminWa = trim($_POST['admin_whatsapp'] ?? '');
        $waAktif = isset($_POST['wa_notif_aktif']) ? '1' : '0';
        $waAdminAktif = isset($_POST['wa_notif_admin_aktif']) ? '1' : '0';

        // Normalisasi nomor WA admin
        $adminWaClean = normalizePhoneNumber($adminWa);

        set_setting('nama_aplikasi', $namaApp);
        set_setting('jam_masuk_normal', $jamMasuk);
        set_setting('jam_masuk_toleransi', $jamToleransi);
        set_setting('jam_pulang_normal', $jamPulang);
        set_setting('fonnte_token', $fonnteToken);
        set_setting('admin_whatsapp', $adminWaClean);
        set_setting('wa_notif_aktif', $waAktif);
        set_setting('wa_notif_admin_aktif', $waAdminAktif);

        $success = 'Konfigurasi pengaturan sistem dan token Fonnte berhasil disimpan!';
    } elseif ($action === 'test_wa') {
        $testTarget = trim($_POST['test_target'] ?? '');
        $testMessage = trim($_POST['test_message'] ?? 'Tes integrasi WhatsApp Fonnte dari sistem karyawan berhasil!');

        if (empty($testTarget)) {
            $error = 'Nomor tujuan tes WhatsApp wajib diisi.';
        } else {
            $testResult = sendWhatsApp($testTarget, $testMessage, 'test_koneksi', 'Admin Test');
            if ($testResult['success']) {
                $success = 'Pesan uji coba WhatsApp BERHASIL dikirim ke ' . h($testTarget) . '!';
            } else {
                $error = 'Uji coba pengiriman GAGAL: ' . h($testResult['message']);
            }
        }
    }
}

// Load current settings
$currAppName = get_setting('nama_aplikasi', 'KERJOO');
$currJamMasuk = get_setting('jam_masuk_normal', '08:00:00');
$currJamToleransi = get_setting('jam_masuk_toleransi', '08:15:00');
$currJamPulang = get_setting('jam_pulang_normal', '17:00:00');
$currToken = get_setting('fonnte_token', '');
$currAdminWa = get_setting('admin_whatsapp', '6281234567890');
$currWaAktif = get_setting('wa_notif_aktif', '1');
$currWaAdminAktif = get_setting('wa_notif_admin_aktif', '0');

$pageTitle = 'Pengaturan Sistem & Fonnte WA';
$pageHeading = 'Pengaturan Sistem';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      
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

      <div class="admin-two-cols">
        
        <!-- Formulir Pengaturan Umum & Token -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-sliders text-accent"></i> Konfigurasi Aplikasi & Jam Kerja</h3>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_settings">

              <div class="form-group">
                <label class="form-label required">Nama Aplikasi</label>
                <input type="text" name="nama_aplikasi" class="form-control" value="<?= h($currAppName) ?>" required>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label required">Jam Masuk Normal</label>
                  <input type="time" name="jam_masuk_normal" class="form-control" value="<?= h(substr($currJamMasuk, 0, 5)) ?>" required>
                </div>

                <div class="form-group">
                  <label class="form-label required">Batas Toleransi Hadir</label>
                  <input type="time" name="jam_masuk_toleransi" class="form-control" value="<?= h(substr($currJamToleransi, 0, 5)) ?>" required>
                  <small class="form-text">Lewat dari waktu ini otomatis berstatus <strong>Terlambat</strong>.</small>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label required">Jam Pulang Standar</label>
                <input type="time" name="jam_pulang_normal" class="form-control" value="<?= h(substr($currJamPulang, 0, 5)) ?>" required>
              </div>

              <hr style="margin: 20px 0; border: none; border-top: 1px solid var(--border-color);">

              <h4 style="font-size: 15px; margin-bottom: 14px; color: var(--primary-dark);">
                <i class="fa-brands fa-whatsapp" style="color: #10b981;"></i> Pengaturan WhatsApp Gateway (Fonnte)
              </h4>

              <div class="form-group">
                <label class="form-label">API Token Fonnte</label>
                <input type="text" name="fonnte_token" class="form-control" placeholder="Masukkan Token API dari dashboard Fonnte.com" value="<?= h($currToken) ?>">
                <small class="form-text">Dapatkan token API gratis di <a href="https://fonnte.com" target="_blank" style="color: var(--accent);">https://fonnte.com</a></small>
              </div>

              <div class="form-group">
                <label class="form-label required">Nomor WhatsApp Admin Penerima Notifikasi</label>
                <input type="text" name="admin_whatsapp" class="form-control" placeholder="Contoh: 081234567890" value="<?= h($currAdminWa) ?>" required>
                <small class="form-text">Admin akan menerima WA saat ada karyawan baru mendaftar.</small>
              </div>

              <div class="form-group" style="margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13.5px;">
                  <input type="checkbox" name="wa_notif_aktif" value="1" <?= ($currWaAktif === '1') ? 'checked' : '' ?>>
                  <span>Aktifkan Pengiriman Notifikasi WhatsApp Otomatis</span>
                </label>
              </div>

              <div class="form-group" style="margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13.5px;">
                  <input type="checkbox" name="wa_notif_admin_aktif" value="1" <?= ($currWaAdminAktif === '1') ? 'checked' : '' ?>>
                  <span>Kirim Salinan Notifikasi Pendaftaran Karyawan ke Nomor Admin</span>
                </label>
                <small class="form-text" style="margin-left: 24px; color: var(--text-muted);">
                  Jika tidak dicentang (direkomendasikan), notifikasi pendaftaran & absensi <strong>hanya</strong> dikirim ke nomor WhatsApp masing-masing karyawan yang bersangkutan, dan tidak akan nyasar ke nomor lain.
                </small>
              </div>

              <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-block">
                  <i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi
                </button>
              </div>

            </form>
          </div>
        </div>

        <!-- Kolom Uji Coba Pengiriman & Panduan Fonnte -->
        <div>
          <!-- Uji Coba Pengiriman WA Live -->
          <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-paper-plane text-accent"></i> Uji Coba Pengiriman WhatsApp</h3>
            </div>
            <div class="card-body">
              <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                Kirim pesan uji coba untuk memverifikasi apakah Token Fonnte dan perangkat WhatsApp Anda sudah terhubung aktif.
              </p>

              <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="test_wa">

                <div class="form-group">
                  <label class="form-label required">Nomor WhatsApp Uji Coba</label>
                  <input type="text" name="test_target" class="form-control" placeholder="Contoh: 081234567890" required>
                </div>

                <div class="form-group">
                  <label class="form-label required">Pesan Tes</label>
                  <textarea name="test_message" class="form-control" rows="3" required>Halo! Ini adalah pesan uji coba dari KERJOO - Sistem Informasi Manajemen Karyawan & Absensi Fonnte Gateway.</textarea>
                </div>

                <button type="submit" class="btn btn-accent btn-block">
                  <i class="fa-solid fa-paper-plane"></i> Kirim Pesan Uji Coba
                </button>
              </form>

              <?php if ($testResult): ?>
                <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 10px; font-size: 12px; border: 1px solid var(--border-color);">
                  <strong>Respons API Fonnte:</strong>
                  <pre style="margin-top: 6px; white-space: pre-wrap; font-family: monospace;"><?= h(json_encode($testResult, JSON_PRETTY_PRINT)) ?></pre>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Panduan Setup Fonnte -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-book-open text-accent"></i> Panduan Setup Fonnte Gateway</h3>
            </div>
            <div class="card-body" style="font-size: 13px; line-height: 1.6; color: var(--text-muted);">
              <ol style="padding-left: 18px; display: grid; gap: 8px;">
                <li>Buka website <a href="https://fonnte.com" target="_blank" style="color:var(--accent); font-weight:600;">https://fonnte.com</a> dan buat akun baru.</li>
                <li>Setelah login, klik menu <strong>Device</strong> lalu scan QR Code menggunakan aplikasi WhatsApp di smartphone Anda.</li>
                <li>Buka menu <strong>API</strong> pada dashboard Fonnte untuk menyalin <strong>API Token</strong> perangkat Anda.</li>
                <li>Tempelkan token ke kolom <strong>API Token Fonnte</strong> di sebelah kiri dan klik <strong>Simpan Konfigurasi</strong>.</li>
                <li>Gunakan form di atas untuk menguji pengiriman live pesan WhatsApp.</li>
              </ol>
            </div>
          </div>

        </div>

      </div>

    </main>
  </div>
</div>

<script src="<?= base_url('assets/js/script.js') ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

