<?php
/**
 * Halaman Absensi Karyawan
 */
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$db = getDB();

$today = date('Y-m-d');
$stmt = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = :uid AND tanggal = :today LIMIT 1");
$stmt->execute([':uid' => $user['id'], ':today' => $today]);
$todayAbsen = $stmt->fetch();

$jamMasukNormal = get_setting('jam_masuk_normal', '08:00:00');
$jamToleransi = get_setting('jam_masuk_toleransi', '08:15:00');
$jamPulangNormal = get_setting('jam_pulang_normal', '17:00:00');

$pageTitle = 'Form Absensi Harian';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mobile-app-container">
  
  <div class="mobile-header" style="padding-bottom: 50px;">
    <div class="mobile-header-top">
      <div>
        <a href="<?= base_url('karyawan/index.php') ?>" style="color: #93c5fd; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
          <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <h2 class="mobile-user-name">Absensi Presensi</h2>
        <div class="mobile-user-role"><?= format_indo_date($today) ?></div>
      </div>
      <div class="mobile-user-avatar">
        <i class="fa-solid fa-fingerprint"></i>
      </div>
    </div>
  </div>

  <div style="padding: 0 20px; margin-top: -30px; position: relative; z-index: 10;">
    
    <!-- Flash Messages -->
    <?php if ($flashSuccess = get_flash('success')): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= h($flashSuccess) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($flashError = get_flash('error')): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= h($flashError) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <!-- Kartu Jam Server & Status -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-body" style="text-align: center; padding: 24px;">
        <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">WAKTU SERVER SAAT INI</span>
        <div class="live-clock" style="font-size: 32px; font-weight: 700; color: var(--primary-dark); margin: 6px 0 16px;">
          --:--:-- WIB
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
          <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 12px;">
            <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Jam Masuk</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--primary-dark);">
              <?= !empty($todayAbsen['jam_masuk']) ? substr($todayAbsen['jam_masuk'], 0, 5) . ' WIB' : '<span style="color:#94a3b8; font-weight:normal;">Belum Absen</span>' ?>
            </div>
            <?php if (!empty($todayAbsen['status'])): ?>
              <span class="badge <?= ($todayAbsen['status'] === 'Hadir') ? 'badge-success' : 'badge-warning' ?>" style="margin-top: 6px;">
                <?= h($todayAbsen['status']) ?>
              </span>
            <?php endif; ?>
          </div>

          <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 12px;">
            <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">Jam Pulang</div>
            <div style="font-size: 16px; font-weight: 700; color: var(--primary-dark);">
              <?= !empty($todayAbsen['jam_pulang']) ? substr($todayAbsen['jam_pulang'], 0, 5) . ' WIB' : '<span style="color:#94a3b8; font-weight:normal;">Belum Absen</span>' ?>
            </div>
            <?php if (!empty($todayAbsen['jam_pulang'])): ?>
              <span class="badge badge-success" style="margin-top: 6px;">Pulang</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tombol Absen -->
        <?php if (!$todayAbsen): ?>
          <form action="<?= base_url('absensi/masuk.php') ?>" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="btn-check-action">
              <i class="fa-solid fa-arrow-right-to-bracket"></i>
              <span>ABSEN MASUK SEKARANG</span>
            </button>
          </form>
        <?php elseif ($todayAbsen && empty($todayAbsen['jam_pulang'])): ?>
          <form action="<?= base_url('absensi/pulang.php') ?>" method="POST">
            <?= csrf_field() ?>
            <button type="submit" class="btn-check-action btn-check-out">
              <i class="fa-solid fa-arrow-right-from-bracket"></i>
              <span>ABSEN PULANG SEKARANG</span>
            </button>
          </form>
        <?php else: ?>
          <div style="padding: 16px; background: #ecfdf5; border-radius: 14px; border: 1px solid #a7f3d0; color: #065f46;">
            <i class="fa-solid fa-circle-check" style="font-size: 24px; margin-bottom: 6px; display: block;"></i>
            <strong>Absensi Hari Ini Lengkap</strong>
            <p style="font-size: 12px; margin-top: 4px;">Terima kasih atas kehadiran dan kerja keras Anda hari ini.</p>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Kartu Barcode & QR Code Absensi Pribadi -->
    <div class="card" style="margin-bottom: 20px; text-align: center;">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 14px;">
          <i class="fa-solid fa-qrcode text-accent"></i> Barcode & QR Code Absen Saya
        </h4>
        <span class="badge badge-info"><?= h($user['nik']) ?></span>
      </div>
      <div class="card-body" style="padding: 20px;">
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
          Tunjukkan kode ini ke Admin / Petugas saat melakukan presensi di kantor:
        </p>

        <!-- QR Code Container -->
        <div id="qrcodeAbsensi" style="display: flex; justify-content: center; margin-bottom: 14px;"></div>

        <!-- 1D Barcode Container -->
        <div>
          <svg id="barcodeSvg"></svg>
        </div>
      </div>
    </div>

    <!-- Ketentuan Jam Kerja -->
    <div class="card">
      <div class="card-header">
        <h4 class="card-title" style="font-size: 14px;">
          <i class="fa-solid fa-circle-info text-accent"></i> Aturan Jam Kerja Kantor
        </h4>
      </div>
      <div class="card-body" style="font-size: 12.5px; line-height: 1.6;">
        <ul style="padding-left: 20px; color: var(--text-muted);">
          <li>Jam masuk normal: <strong><?= substr($jamMasukNormal, 0, 5) ?> WIB</strong></li>
          <li>Batas toleransi hadir: <strong><?= substr($jamToleransi, 0, 5) ?> WIB</strong> (absen lewat dari jam ini berstatus <em>Terlambat</em>)</li>
          <li>Jam kepulangan kantor: <strong><?= substr($jamPulangNormal, 0, 5) ?> WIB</strong></li>
          <li>Notifikasi konfirmasi kehadiran otomatis dikirimkan ke nomor WhatsApp Anda via Fonnte Gateway.</li>
        </ul>
      </div>
    </div>

  </div>

  <!-- Bottom Navbar -->
  <nav class="bottom-navbar">
    <a href="<?= base_url('karyawan/index.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </a>
    <a href="<?= base_url('absensi/index.php') ?>" class="bottom-nav-item active">
      <i class="fa-solid fa-calendar-check"></i>
      <span>Absen</span>
    </a>
    <a href="<?= base_url('absensi/riwayat.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <span>Riwayat</span>
    </a>
    <a href="<?= base_url('karyawan/profil.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-user"></i>
      <span>Profile</span>
    </a>
  </nav>

</div>

<!-- Library Generator Barcode & QR Code -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="<?= base_url('assets/js/script.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const nik = <?= json_encode($user['nik']) ?>;

  // 1. Generate QR Code
  new QRCode(document.getElementById("qrcodeAbsensi"), {
    text: nik,
    width: 140,
    height: 140,
    colorDark : "#0b2863",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
  });

  // 2. Generate 1D Barcode
  JsBarcode("#barcodeSvg", nik, {
    format: "CODE128",
    lineColor: "#0b2863",
    width: 1.8,
    height: 45,
    displayValue: true,
    fontSize: 13,
    font: "Poppins"
  });
});
</script>
</body>
</html>

