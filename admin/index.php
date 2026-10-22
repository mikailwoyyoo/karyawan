<?php
/**
 * Admin Dashboard - Pusat Kontrol & Analitik Kehadiran
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();
$today = date('Y-m-d');

// 1. STATISTIK KARTU UTAMA
// Total Karyawan (role = karyawan)
$totalKaryawan = (int)$db->query("SELECT COUNT(*) FROM karyawan WHERE role = 'karyawan'")->fetchColumn();

// Absensi Hari Ini
$stmtToday = $db->prepare("SELECT 
    COUNT(CASE WHEN jam_masuk IS NOT NULL THEN 1 END) as sudah_absen,
    COUNT(CASE WHEN jam_pulang IS NOT NULL THEN 1 END) as sudah_pulang,
    COUNT(CASE WHEN status = 'Terlambat' THEN 1 END) as total_terlambat,
    COUNT(CASE WHEN status = 'Hadir' THEN 1 END) as total_hadir,
    COUNT(*) as total_absensi
    FROM absensi 
    WHERE tanggal = :today");
$stmtToday->execute([':today' => $today]);
$statsToday = $stmtToday->fetch();

$sudahAbsen = (int)($statsToday['sudah_absen'] ?? 0);
$belumAbsen = max(0, $totalKaryawan - $sudahAbsen);
$sudahPulang = (int)($statsToday['sudah_pulang'] ?? 0);
$totalTerlambat = (int)($statsToday['total_terlambat'] ?? 0);
$totalAbsensiToday = (int)($statsToday['total_absensi'] ?? 0);

// 2. DATA GRAFIK 7 HARI TERAKHIR (CHART.JS)
$chartDates = [];
$chartHadir = [];
$chartTerlambat = [];
$chartLainnya = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartDates[] = date('d M', strtotime($d));

    $stmtDay = $db->prepare("SELECT 
        COUNT(CASE WHEN status = 'Hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN status = 'Terlambat' THEN 1 END) as terlambat,
        COUNT(CASE WHEN status IN ('Izin', 'Sakit', 'Alpha') THEN 1 END) as lainnya
        FROM absensi WHERE tanggal = :d");
    $stmtDay->execute([':d' => $d]);
    $dayRow = $stmtDay->fetch();

    $chartHadir[] = (int)$dayRow['hadir'];
    $chartTerlambat[] = (int)$dayRow['terlambat'];
    $chartLainnya[] = (int)$dayRow['lainnya'];
}

// 3. DAFTAR AKTIVITAS TERBARU HARI INI
$stmtRecent = $db->prepare("SELECT a.*, k.nama, k.nik, k.jabatan 
    FROM absensi a 
    JOIN karyawan k ON a.id_karyawan = k.id 
    WHERE a.tanggal = :today 
    ORDER BY COALESCE(a.jam_pulang, a.jam_masuk) DESC LIMIT 10");
$stmtRecent->execute([':today' => $today]);
$recentActivities = $stmtRecent->fetchAll();

$pageTitle = 'Dashboard Administrator';
$pageHeading = 'Dashboard Analitik';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <!-- Sidebar -->
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <!-- Navbar -->
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      
      <!-- Flash Alert -->
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

      <!-- 1. Statistik Kartu (6 Kartu Sesuai Kebutuhan) -->
      <div class="stats-grid">
        
        <div class="stat-card">
          <div class="stat-icon icon-blue">
            <i class="fa-solid fa-users"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Total Karyawan</div>
            <div class="stat-value"><?= $totalKaryawan ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-green">
            <i class="fa-solid fa-user-check"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Sudah Absen Masuk</div>
            <div class="stat-value"><?= $sudahAbsen ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-rose">
            <i class="fa-solid fa-user-xmark"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Belum Absen Hari Ini</div>
            <div class="stat-value"><?= $belumAbsen ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-purple">
            <i class="fa-solid fa-door-open"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Sudah Pulang</div>
            <div class="stat-value"><?= $sudahPulang ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-amber">
            <i class="fa-solid fa-user-clock"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Karyawan Terlambat</div>
            <div class="stat-value"><?= $totalTerlambat ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-indigo">
            <i class="fa-solid fa-clipboard-check"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Total Absensi Hari Ini</div>
            <div class="stat-value"><?= $totalAbsensiToday ?></div>
          </div>
        </div>

      </div>

      <!-- 2. Baris Grafik Tren Absensi 7 Hari -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa-solid fa-chart-area text-accent"></i> Tren Kehadiran Karyawan (7 Hari Terakhir)
          </h3>
          <span style="font-size: 12px; color: var(--text-muted);">Diperbarui otomatis dari database</span>
        </div>
        <div class="card-body">
          <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="attendanceChart"></canvas>
          </div>
        </div>
      </div>

      <!-- 3. Tabel Aktivitas Kehadiran Hari Ini -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa-solid fa-clock-rotate-left text-accent"></i> Aktivitas Absensi Terbaru Hari Ini (<?= format_indo_date($today) ?>)
          </h3>
          <a href="<?= base_url('admin/absensi.php') ?>" class="btn btn-outline btn-sm">
            Lihat Semua Absensi <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>

        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
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
              <?php if (empty($recentActivities)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 32px; color: var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                    Belum ada aktivitas absensi tercatat pada hari ini.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = 1; foreach ($recentActivities as $act): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge badge-secondary"><?= h($act['nik']) ?></span></td>
                    <td><strong><?= h($act['nama']) ?></strong></td>
                    <td><?= h($act['jabatan']) ?></td>
                    <td>
                      <?= !empty($act['jam_masuk']) ? '<span style="font-weight:600; color:var(--primary);">' . substr($act['jam_masuk'], 0, 5) . ' WIB</span>' : '-' ?>
                    </td>
                    <td>
                      <?= !empty($act['jam_pulang']) ? '<span style="font-weight:600; color:#ea580c;">' . substr($act['jam_pulang'], 0, 5) . ' WIB</span>' : '<span class="text-muted">Belum Pulang</span>' ?>
                    </td>
                    <td>
                      <span class="badge <?= ($act['status'] === 'Hadir') ? 'badge-success' : (($act['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
                        <?= h($act['status']) ?>
                      </span>
                    </td>
                    <td><small class="text-muted"><?= h($act['keterangan'] ?? '-') ?></small></td>
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

<script>
// Chart.js Rendering
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartDates) ?>,
    datasets: [
      {
        label: 'Hadir Tepat Waktu',
        data: <?= json_encode($chartHadir) ?>,
        backgroundColor: '#10b981',
        borderRadius: 6
      },
      {
        label: 'Terlambat',
        data: <?= json_encode($chartTerlambat) ?>,
        backgroundColor: '#f59e0b',
        borderRadius: 6
      },
      {
        label: 'Izin / Sakit',
        data: <?= json_encode($chartLainnya) ?>,
        backgroundColor: '#6366f1',
        borderRadius: 6
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top',
        labels: { 
          boxWidth: 10, 
          font: { family: 'Poppins', size: window.innerWidth < 576 ? 10 : 12 } 
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { 
          stepSize: 1, 
          font: { family: 'Poppins', size: window.innerWidth < 576 ? 10 : 11 } 
        }
      },
      x: {
        grid: { display: false },
        ticks: { 
          font: { family: 'Poppins', size: window.innerWidth < 576 ? 10 : 11 } 
        }
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

