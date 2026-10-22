<?php
/**
 * Dashboard Utama Karyawan - Mobile First & Responsive
 * Desain Mengikuti Mockup Aplikasi HR Modern
 */
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$db = getDB();

// Greeting berdasarkan jam server
$hour = (int)date('H');
if ($hour >= 4 && $hour < 11) {
    $greeting = 'Selamat Pagi,';
} elseif ($hour >= 11 && $hour < 15) {
    $greeting = 'Selamat Siang,';
} elseif ($hour >= 15 && $hour < 18) {
    $greeting = 'Selamat Sore,';
} else {
    $greeting = 'Selamat Malam,';
}

$today = date('Y-m-d');

// Ambil data absensi hari ini untuk karyawan yang login
$stmt = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = :uid AND tanggal = :today LIMIT 1");
$stmt->execute([':uid' => $user['id'], ':today' => $today]);
$todayAbsensi = $stmt->fetch();

// Ambil riwayat absensi 5 hari terakhir
$stmtHistory = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = :uid ORDER BY tanggal DESC LIMIT 5");
$stmtHistory->execute([':uid' => $user['id']]);
$recentAttendance = $stmtHistory->fetchAll();

// Jam operasional
$jamMasukNormal = get_setting('jam_masuk_normal', '08:00:00');
$jamPulangNormal = get_setting('jam_pulang_normal', '17:00:00');
$workingScheduleStr = substr($jamMasukNormal, 0, 5) . ' - ' . substr($jamPulangNormal, 0, 5);

$pageTitle = 'Dashboard Karyawan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mobile-app-container">
  
  <!-- 1. Header Lengkung Biru Tua (Matches Reference Screenshot) -->
  <div class="mobile-header">
    <div class="mobile-header-top">
      <div>
        <div class="mobile-greeting-label"><?= $greeting ?></div>
        <h2 class="mobile-user-name"><?= h($user['nama']) ?></h2>
        <div class="mobile-user-role"><?= h($user['jabatan']) ?> &bull; NIK: <?= h($user['nik']) ?></div>
      </div>
      <a href="<?= base_url('karyawan/profil.php') ?>" class="mobile-user-avatar" title="Lihat Profil">
        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
      </a>
    </div>
  </div>

  <!-- 2. Kartu Jadwal Kerja & Tombol Check In / Check Out Floating -->
  <div class="schedule-card">
    <div class="schedule-header">
      <span class="schedule-title"><i class="fa-regular fa-calendar-check" style="color: var(--accent); margin-right: 6px;"></i> Working Schedule</span>
      <span class="schedule-date"><?= format_indo_date($today) ?></span>
    </div>

    <div class="schedule-time-box">
      <div class="schedule-hours"><?= $workingScheduleStr ?></div>
      <div class="schedule-clock">
        <i class="fa-solid fa-clock"></i>
        <span class="live-clock">--:--:-- WIB</span>
      </div>
    </div>

    <!-- Alert Flash Messages -->
    <?php if ($flashSuccess = get_flash('success')): ?>
      <div class="alert alert-success" style="padding: 10px 14px; font-size: 13px; margin-bottom: 14px;">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= h($flashSuccess) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <?php if ($flashError = get_flash('error')): ?>
      <div class="alert alert-danger" style="padding: 10px 14px; font-size: 13px; margin-bottom: 14px;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= h($flashError) ?></span>
        <button class="alert-close">&times;</button>
      </div>
    <?php endif; ?>

    <!-- Tombol Aksi Absensi Berdasarkan Status Hari Ini -->
    <?php if (!$todayAbsensi): ?>
      <!-- Belum Absen Masuk -->
      <form action="<?= base_url('absensi/masuk.php') ?>" method="POST">
        <?= csrf_field() ?>
        <button type="submit" class="btn-check-action">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>
          <span>Check In (Absen Masuk)</span>
        </button>
      </form>
    <?php elseif ($todayAbsensi && empty($todayAbsensi['jam_pulang'])): ?>
      <!-- Sudah Absen Masuk, Belum Absen Pulang -->
      <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; text-align: center;">
        Masuk: <strong><?= substr($todayAbsensi['jam_masuk'], 0, 5) ?> WIB</strong> 
        (Status: <span class="badge <?= ($todayAbsensi['status'] === 'Hadir') ? 'badge-success' : 'badge-warning' ?>"><?= h($todayAbsensi['status']) ?></span>)
      </div>
      <form action="<?= base_url('absensi/pulang.php') ?>" method="POST">
        <?= csrf_field() ?>
        <button type="submit" class="btn-check-action btn-check-out">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Check Out (Absen Pulang)</span>
        </button>
      </form>
    <?php else: ?>
      <!-- Sudah Absen Masuk dan Pulang Lengkap -->
      <button type="button" class="btn-check-action btn-check-done" disabled>
        <i class="fa-solid fa-circle-check"></i>
        <span>Absensi Hari Ini Lengkap</span>
      </button>
      <div style="font-size: 12px; color: var(--text-muted); margin-top: 10px; text-align: center;">
        Masuk: <strong><?= substr($todayAbsensi['jam_masuk'], 0, 5) ?></strong> &bull; 
        Pulang: <strong><?= substr($todayAbsensi['jam_pulang'], 0, 5) ?> WIB</strong>
      </div>
    <?php endif; ?>

    <!-- Tombol Tampilkan Barcode Absensi -->
    <div style="margin-top: 14px; text-align: center;">
      <button type="button" class="btn btn-outline btn-block btn-sm" id="btnShowBarcodeModal" style="border-radius: var(--radius-md); font-weight: 600; color: var(--primary);">
        <i class="fa-solid fa-qrcode text-accent"></i> Tampilkan Barcode / QR Absen Saya
      </button>
    </div>
  </div>

  <!-- Modal Barcode & QR Code Digital Karyawan -->
  <div class="modal-overlay" id="barcodeModal">
    <div class="modal-box" style="text-align: center; max-width: 380px;">
      <div class="modal-header">
        <h4 class="modal-title"><i class="fa-solid fa-id-card text-accent"></i> Kartu Barcode Absensi</h4>
        <button type="button" class="modal-close" id="btnCloseBarcodeModal">&times;</button>
      </div>
      <div class="modal-body" style="padding: 24px 20px;">
        <div style="font-size: 15px; font-weight: 700; color: var(--primary-dark); margin-bottom: 2px;">
          <?= h($user['nama']) ?>
        </div>
        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
          <?= h($user['jabatan']) ?> &bull; NIK: <strong><?= h($user['nik']) ?></strong>
        </div>

        <!-- Wadah QR Code -->
        <div id="qrcodeContainer" style="display: flex; justify-content: center; margin-bottom: 16px; padding: 12px; background: #fff; border-radius: 12px; border: 1px solid var(--border-color); width: fit-content; margin-left: auto; margin-right: auto;"></div>

        <!-- Wadah 1D Barcode -->
        <div style="margin-bottom: 8px;">
          <svg id="barcodeCanvas"></svg>
        </div>

        <p style="font-size: 11.5px; color: var(--text-muted);">
          Tunjukkan Barcode atau QR Code ini kepada Admin / Scanner saat absensi masuk atau pulang.
        </p>
      </div>
      <div class="modal-footer" style="justify-content: center;">
        <button type="button" class="btn btn-primary btn-sm" id="btnDismissBarcode">Tutup Kartu</button>
      </div>
    </div>
  </div>

  <!-- 3. Menu Cepat 4 Ikon (Quick Action Grid) -->
  <div class="quick-actions-card">
    <a href="<?= base_url('absensi/riwayat.php') ?>" class="quick-action-item">
      <div class="quick-action-icon qa-green">
        <i class="fa-solid fa-clipboard-list"></i>
      </div>
      <span class="quick-action-title">Riwayat Absen</span>
    </a>

    <a href="<?= base_url('absensi/index.php') ?>" class="quick-action-item">
      <div class="quick-action-icon qa-blue">
        <i class="fa-solid fa-business-time"></i>
      </div>
      <span class="quick-action-title">Kehadiran</span>
    </a>

    <a href="<?= base_url('karyawan/profil.php') ?>" class="quick-action-item">
      <div class="quick-action-icon qa-orange">
        <i class="fa-solid fa-id-badge"></i>
      </div>
      <span class="quick-action-title">Data Profil</span>
    </a>

    <a href="<?= base_url('auth/logout.php') ?>" class="quick-action-item">
      <div class="quick-action-icon qa-purple">
        <i class="fa-solid fa-power-off"></i>
      </div>
      <span class="quick-action-title">Keluar</span>
    </a>
  </div>

  <!-- 4. Riwayat Absensi Terkini (Matches Reference Cards) -->
  <div class="mobile-section">
    <div class="section-header">
      <h3 class="section-title">Attendance</h3>
      <a href="<?= base_url('absensi/riwayat.php') ?>" class="section-link">View All</a>
    </div>

    <?php if (empty($recentAttendance)): ?>
      <div class="attendance-card" style="text-align: center; color: var(--text-muted); padding: 30px 20px;">
        <i class="fa-solid fa-calendar-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px;"></i>
        <p>Belum ada catatan riwayat absensi.</p>
      </div>
    <?php else: ?>
      <?php foreach ($recentAttendance as $att): ?>
        <div class="attendance-card">
          <div class="attendance-card-header">
            <span class="attendance-card-date"><?= format_indo_date($att['tanggal']) ?></span>
            <span class="badge <?= ($att['status'] === 'Hadir') ? 'badge-success' : (($att['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
              <?= h($att['status']) ?>
            </span>
          </div>

          <div class="attendance-card-body">
            <div class="time-slot">
              <div class="time-pin-icon">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div class="time-slot-details">
                <div class="time-slot-label">Start Day</div>
                <div class="time-slot-val"><?= !empty($att['jam_masuk']) ? substr($att['jam_masuk'], 0, 5) : '--:--' ?></div>
              </div>
            </div>

            <div class="time-slot">
              <div class="time-pin-icon" style="background: #e0e7ff; color: #4f46e5;">
                <i class="fa-solid fa-location-pin"></i>
              </div>
              <div class="time-slot-details">
                <div class="time-slot-label">End Day</div>
                <div class="time-slot-val"><?= !empty($att['jam_pulang']) ? substr($att['jam_pulang'], 0, 5) : '--:--' ?></div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- 5. Floating Bottom Navbar (Matches Reference Mockup) -->
  <nav class="bottom-navbar">
    <a href="<?= base_url('karyawan/index.php') ?>" class="bottom-nav-item active">
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
  const barcodeModal = document.getElementById('barcodeModal');
  const btnShow = document.getElementById('btnShowBarcodeModal');
  const btnClose = document.getElementById('btnCloseBarcodeModal');
  const btnDismiss = document.getElementById('btnDismissBarcode');
  let isGenerated = false;

  function generateCodes() {
    if (isGenerated) return;
    const nik = <?= json_encode($user['nik']) ?>;
    
    // 1. Generate QR Code
    new QRCode(document.getElementById("qrcodeContainer"), {
      text: nik,
      width: 150,
      height: 150,
      colorDark : "#0b2863",
      colorLight : "#ffffff",
      correctLevel : QRCode.CorrectLevel.H
    });

    // 2. Generate 1D Barcode
    JsBarcode("#barcodeCanvas", nik, {
      format: "CODE128",
      lineColor: "#0b2863",
      width: 1.8,
      height: 45,
      displayValue: true,
      fontSize: 13,
      font: "Poppins"
    });

    isGenerated = true;
  }

  if (btnShow && barcodeModal) {
    btnShow.addEventListener('click', function () {
      generateCodes();
      barcodeModal.classList.add('show');
    });

    if (btnClose) btnClose.addEventListener('click', () => barcodeModal.classList.remove('show'));
    if (btnDismiss) btnDismiss.addEventListener('click', () => barcodeModal.classList.remove('show'));
  }
});
</script>
</body>
</html>

