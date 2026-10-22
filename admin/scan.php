<?php
/**
 * Admin - Scanner Barcode & QR Code Absensi Karyawan
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();
$today = date('Y-m-d');

// Ambil riwayat absensi hari ini
$stmtToday = $db->prepare("SELECT a.*, k.nama, k.nik, k.jabatan 
    FROM absensi a 
    JOIN karyawan k ON a.id_karyawan = k.id 
    WHERE a.tanggal = :today 
    ORDER BY COALESCE(a.jam_pulang, a.jam_masuk) DESC LIMIT 15");
$stmtToday->execute([':today' => $today]);
$recentScans = $stmtToday->fetchAll();

$pageTitle = 'Scan Barcode & QR Code Absensi';
$pageHeading = 'Scanner Absensi';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- HTML5-QRCode Scanner CDN -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      
      <div class="admin-two-cols">
        
        <!-- Kolom Kiri: Kamera Scanner & Mode -->
        <div>
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fa-solid fa-qrcode text-accent"></i> Kamera Scanner Barcode / QR Code
              </h3>
              <div class="scanner-mode-switch">
                <label class="mode-pill active" id="modeMasukLabel">
                  <input type="radio" name="scan_mode" value="masuk" checked>
                  <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk
                </label>
                <label class="mode-pill" id="modePulangLabel">
                  <input type="radio" name="scan_mode" value="pulang">
                  <i class="fa-solid fa-arrow-right-from-bracket"></i> Pulang
                </label>
              </div>
            </div>

            <div class="card-body" style="text-align: center;">
              
              <!-- Video Viewport Kamera -->
              <div id="reader" style="width: 100%; max-width: 440px; margin: 0 auto; border-radius: var(--radius-lg); overflow: hidden; border: 2px dashed #cbd5e1; background: #0f172a; min-height: 280px; display: flex; align-items: center; justify-content: center; position: relative;">
                <div id="scannerPlaceholder" style="color: #94a3b8; padding: 20px;">
                  <i class="fa-solid fa-camera" style="font-size: 40px; margin-bottom: 12px; display: block; color: var(--accent);"></i>
                  <p style="font-size: 13px;">Klik tombol di bawah untuk mengaktifkan kamera scanner</p>
                </div>
              </div>

              <!-- Kontrol Tombol Kamera -->
              <div style="margin-top: 16px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <button type="button" id="btnStartScan" class="btn btn-accent btn-sm">
                  <i class="fa-solid fa-video"></i> Aktifkan Kamera
                </button>
                <button type="button" id="btnStopScan" class="btn btn-outline btn-sm" style="display: none;">
                  <i class="fa-solid fa-video-slash"></i> Nonaktifkan Kamera
                </button>
              </div>

              <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: left;">
                <label class="form-label" style="font-size: 11px;">
                  <i class="fa-solid fa-barcode"></i> Atau Input Manual / USB Laser Barcode Gun:
                </label>
                <div style="display: flex; gap: 8px;">
                  <input type="text" id="manualNikInput" class="form-control" placeholder="Scan Barcode atau ketik NIK..." style="font-size: 13px;" autofocus>
                  <button type="button" id="btnSubmitManual" class="btn btn-primary btn-sm">Proses</button>
                </div>
                <small class="form-text">Tekan Enter atau tembak dengan barcode scanner USB untuk memproses otomatis.</small>
              </div>

            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Hasil Live Scan & Feedback -->
        <div>
          <!-- Kartu Hasil Scan Real-Time -->
          <div class="card" id="scanResultCard" style="display: none; border-left: 6px solid #10b981; animation: slideDown 0.3s ease;">
            <div class="card-body" style="padding: 20px;">
              <div style="display: flex; align-items: flex-start; gap: 16px;">
                <div id="resAvatar" style="width: 56px; height: 56px; border-radius: 16px; background: #10b981; color:#fff; display:flex; align-items:center; justify-content:center; font-size: 24px; font-weight:700; flex-shrink: 0;">
                  ✓
                </div>
                <div style="flex: 1;">
                  <div style="display: flex; align-items: center; justify-content: space-between;">
                    <h3 id="resNama" style="font-size: 18px; margin-bottom: 2px;">Nama Karyawan</h3>
                    <span id="resBadge" class="badge badge-success">HADIR</span>
                  </div>
                  <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                    <span id="resNik">NIK</span> &bull; <span id="resJabatan">Jabatan</span>
                  </div>
                  <div style="padding: 8px 12px; background: #f8fafc; border-radius: 8px; font-size: 13px; border: 1px solid var(--border-color); margin-bottom: 8px;">
                    <strong id="resMessage">Status pesan</strong>
                  </div>
                  <div style="font-size: 12px; color: #059669; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-brands fa-whatsapp"></i> <span id="resWaStatus">Notifikasi WhatsApp terkirim</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Petunjuk Pemindaian -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-circle-question text-accent"></i> Petunjuk Penggunaan Scanner</h3>
            </div>
            <div class="card-body" style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
              <ol style="padding-left: 18px; display: grid; gap: 8px;">
                <li>Pilih mode absensi: <strong>Masuk</strong> untuk check-in pagi, atau <strong>Pulang</strong> untuk check-out sore.</li>
                <li>Minta karyawan menunjukkan <strong>QR Code / Barcode</strong> absensi dari layar smartphone mereka (menu Absen di akun karyawan).</li>
                <li>Arahkan kamera ke barcode/QR Code karyawan hingga terdengar bunyi notifikasi <em>beep</em>.</li>
                <li>Sistem akan mencatat waktu kehadiran secara instan dan mengirimkan konfirmasi via **WhatsApp Gateway Fonnte** ke nomor HP karyawan tersebut!</li>
              </ol>
            </div>
          </div>

        </div>

      </div>

      <!-- Tabel Riwayat Pemindaian Sesi Hari Ini -->
      <div class="card" style="margin-top: 24px;">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa-solid fa-list-check text-accent"></i> Riwayat Absensi Hari Ini (<?= format_indo_date($today) ?>)
          </h3>
          <span style="font-size: 12px; color: var(--text-muted);">Diperbarui secara otomatis setiap kali barcode discan</span>
        </div>
        <div class="table-responsive">
          <table class="table" id="tableRecentScans">
            <thead>
              <tr>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Jabatan</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentScans)): ?>
                <tr id="emptyScanRow">
                  <td colspan="7" style="text-align: center; padding: 28px; color: var(--text-muted);">
                    Belum ada karyawan yang melakukan absensi hari ini.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentScans as $sc): ?>
                  <tr>
                    <td><span class="badge badge-secondary"><?= h($sc['nik']) ?></span></td>
                    <td><strong><?= h($sc['nama']) ?></strong></td>
                    <td><?= h($sc['jabatan']) ?></td>
                    <td><?= !empty($sc['jam_masuk']) ? substr($sc['jam_masuk'], 0, 5) . ' WIB' : '-' ?></td>
                    <td><?= !empty($sc['jam_pulang']) ? substr($sc['jam_pulang'], 0, 5) . ' WIB' : '-' ?></td>
                    <td>
                      <span class="badge <?= ($sc['status'] === 'Hadir') ? 'badge-success' : (($sc['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
                        <?= h($sc['status']) ?>
                      </span>
                    </td>
                    <td><small class="text-muted"><?= h($sc['keterangan'] ?? '-') ?></small></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</div>

<style>
.scanner-mode-switch {
  display: flex;
  background: #f1f5f9;
  border-radius: var(--radius-sm);
  padding: 3px;
  gap: 4px;
}
.mode-pill {
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--text-muted);
  transition: var(--transition);
}
.mode-pill input { display: none; }
.mode-pill.active {
  background: var(--accent);
  color: #fff;
  box-shadow: 0 2px 6px rgba(99, 102, 241, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // 1. Audio Beep Feedback (Web Audio API Synthesizer)
  function playBeep(success = true) {
    try {
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.connect(gain);
      gain.connect(audioCtx.destination);
      
      if (success) {
        osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 high note
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
      } else {
        osc.frequency.setValueAtTime(300, audioCtx.currentTime); // Low buzz
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.3);
      }
    } catch(e) {}
  }

  // 2. Mode Switch Handler (Masuk / Pulang)
  const modeInputs = document.querySelectorAll('input[name="scan_mode"]');
  const modeLabels = document.querySelectorAll('.mode-pill');
  let currentMode = 'masuk';

  modeInputs.forEach(input => {
    input.addEventListener('change', function () {
      modeLabels.forEach(lbl => lbl.classList.remove('active'));
      this.closest('.mode-pill').classList.add('active');
      currentMode = this.value;
    });
  });

  // 3. HTML5 QR Code Scanner Setup
  let html5QrCode = null;
  const btnStart = document.getElementById('btnStartScan');
  const btnStop = document.getElementById('btnStopScan');
  const placeholder = document.getElementById('scannerPlaceholder');
  let isScanning = false;
  let isProcessing = false;

  btnStart.addEventListener('click', function () {
    if (placeholder) placeholder.style.display = 'none';
    html5QrCode = new Html5Qrcode("reader");

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    html5QrCode.start(
      { facingMode: "environment" },
      config,
      onScanSuccess,
      onScanFailure
    ).then(() => {
      isScanning = true;
      btnStart.style.display = 'none';
      btnStop.style.display = 'inline-flex';
    }).catch(err => {
      alert("Gagal mengakses kamera: " + err);
      if (placeholder) placeholder.style.display = 'block';
    });
  });

  btnStop.addEventListener('click', function () {
    if (html5QrCode && isScanning) {
      html5QrCode.stop().then(() => {
        isScanning = false;
        btnStart.style.display = 'inline-flex';
        btnStop.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
      });
    }
  });

  function onScanSuccess(decodedText, decodedResult) {
    if (isProcessing) return; // Prevent double firing
    isProcessing = true;
    processAttendance(decodedText);
    setTimeout(() => { isProcessing = false; }, 2500); // 2.5s cooldown
  }

  function onScanFailure(error) {
    // Silent ignore continuous frame scan failures
  }

  // 4. USB Barcode Gun / Manual Input Trigger
  const manualInput = document.getElementById('manualNikInput');
  const btnSubmitManual = document.getElementById('btnSubmitManual');

  btnSubmitManual.addEventListener('click', function () {
    const val = manualInput.value.trim();
    if (val) {
      processAttendance(val);
      manualInput.value = '';
    }
  });

  manualInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      btnSubmitManual.click();
    }
  });

  // 5. Kirim data scan ke backend via AJAX
  function processAttendance(nik) {
    fetch('<?= base_url('api/scan_process.php') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nik: nik, mode: currentMode })
    })
    .then(res => res.json())
    .then(data => {
      displayScanResult(data);
    })
    .catch(err => {
      displayScanResult({
        success: false,
        message: 'Koneksi ke server gagal: ' + err
      });
    });
  }

  // 6. Tampilkan Hasil Pemindaian di Layar
  const resultCard = document.getElementById('scanResultCard');
  const resNama = document.getElementById('resNama');
  const resNik = document.getElementById('resNik');
  const resJabatan = document.getElementById('resJabatan');
  const resBadge = document.getElementById('resBadge');
  const resMessage = document.getElementById('resMessage');
  const resWaStatus = document.getElementById('resWaStatus');
  const resAvatar = document.getElementById('resAvatar');

  function displayScanResult(res) {
    resultCard.style.display = 'block';

    if (res.success) {
      playBeep(true);
      resultCard.style.borderLeftColor = '#10b981';
      resAvatar.style.background = '#10b981';
      resAvatar.innerHTML = '<i class="fa-solid fa-check"></i>';
      resNama.textContent = res.karyawan.nama;
      resNik.textContent = res.karyawan.nik;
      resJabatan.textContent = res.karyawan.jabatan;
      resBadge.className = 'badge ' + (res.status === 'Hadir' ? 'badge-success' : (res.status === 'Terlambat' ? 'badge-warning' : 'badge-info'));
      resBadge.textContent = res.status.toUpperCase();
      resMessage.textContent = res.message + ' (' + res.jam + ')';
      resWaStatus.textContent = res.wa_status;

      // Update tabel langsung di sesi
      addScanToTable(res.karyawan, res.status, res.jam, res.type);
    } else {
      playBeep(false);
      resultCard.style.borderLeftColor = '#ef4444';
      resAvatar.style.background = '#ef4444';
      resAvatar.innerHTML = '<i class="fa-solid fa-xmark"></i>';
      resNama.textContent = res.karyawan ? res.karyawan.nama : 'Peringatan';
      resNik.textContent = res.karyawan ? res.karyawan.nik : '-';
      resJabatan.textContent = res.karyawan ? res.karyawan.jabatan : '-';
      resBadge.className = 'badge badge-danger';
      resBadge.textContent = 'GAGAL';
      resMessage.textContent = res.message;
      resWaStatus.textContent = '-';
    }

    resultCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function addScanToTable(karyawan, status, jam, type) {
    const tableBody = document.querySelector('#tableRecentScans tbody');
    const emptyRow = document.getElementById('emptyScanRow');
    if (emptyRow) emptyRow.remove();

    const newRow = document.createElement('tr');
    newRow.style.backgroundColor = '#ecfdf5';
    newRow.innerHTML = `
      <td><span class="badge badge-secondary">${karyawan.nik}</span></td>
      <td><strong>${karyawan.nama}</strong></td>
      <td>${karyawan.jabatan}</td>
      <td>${type === 'masuk' ? jam : '-'}</td>
      <td>${type === 'pulang' ? jam : '-'}</td>
      <td><span class="badge badge-success">${status}</span></td>
      <td><small class="text-muted">Scan Barcode Live</small></td>
    `;
    tableBody.insertBefore(newRow, tableBody.firstChild);
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

