<?php
/**
 * Admin - Laporan Rekapitulasi Absensi & Filter
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();

// Filter Tanggal (Default: Awal bulan ini sampai hari ini)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$karyawanId = (int)($_GET['karyawan_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Ambil daftar seluruh karyawan untuk dropdown filter
$karyawanOptions = $db->query("SELECT id, nik, nama FROM karyawan ORDER BY nama ASC")->fetchAll();

// Query conditions
$where = ["a.tanggal BETWEEN :start AND :end"];
$params = [
    ':start' => $startDate,
    ':end' => $endDate
];

if ($karyawanId > 0) {
    $where[] = "a.id_karyawan = :kid";
    $params[':kid'] = $karyawanId;
}

if (!empty($statusFilter)) {
    $where[] = "a.status = :st";
    $params[':st'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

// Ringkasan Statistik Berdasarkan Filter
$stmtSummary = $db->prepare("SELECT 
    COUNT(CASE WHEN a.status = 'Hadir' THEN 1 END) as hadir,
    COUNT(CASE WHEN a.status = 'Terlambat' THEN 1 END) as terlambat,
    COUNT(CASE WHEN a.status = 'Izin' THEN 1 END) as izin,
    COUNT(CASE WHEN a.status = 'Sakit' THEN 1 END) as sakit,
    COUNT(*) as total
    FROM absensi a 
    WHERE $whereSql");
$stmtSummary->execute($params);
$summary = $stmtSummary->fetch();

// Total Baris untuk pagination
$stmtCount = $db->prepare("SELECT COUNT(*) FROM absensi a JOIN karyawan k ON a.id_karyawan = k.id WHERE $whereSql");
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Ambil data laporan
$stmtLaporan = $db->prepare("SELECT a.*, k.nama, k.nik, k.jabatan 
    FROM absensi a 
    JOIN karyawan k ON a.id_karyawan = k.id 
    WHERE $whereSql 
    ORDER BY a.tanggal DESC, a.jam_masuk DESC 
    LIMIT $limit OFFSET $offset");
$stmtLaporan->execute($params);
$laporanList = $stmtLaporan->fetchAll();

// Build Query string untuk export
$exportQuery = http_build_query([
    'start_date' => $startDate,
    'end_date' => $endDate,
    'karyawan_id' => $karyawanId,
    'status' => $statusFilter
]);

$pageTitle = 'Laporan Rekapitulasi Absensi';
$pageHeading = 'Laporan Kehadiran';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      
      <!-- Filter Bar -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h3 class="card-title"><i class="fa-solid fa-filter text-accent"></i> Filter Rekapitulasi Laporan</h3>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="<?= base_url('admin/export_excel.php?' . $exportQuery) ?>" class="btn btn-success btn-sm">
              <i class="fa-solid fa-file-excel"></i> Export Excel
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline btn-sm">
              <i class="fa-solid fa-print"></i> Cetak / PDF
            </button>
          </div>
        </div>

        <div class="card-body">
          <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; align-items: flex-end;">
            <div>
              <label class="form-label" style="font-size: 11px;">Dari Tanggal</label>
              <input type="date" name="start_date" class="form-control" value="<?= h($startDate) ?>" required>
            </div>

            <div>
              <label class="form-label" style="font-size: 11px;">Sampai Tanggal</label>
              <input type="date" name="end_date" class="form-control" value="<?= h($endDate) ?>" required>
            </div>

            <div>
              <label class="form-label" style="font-size: 11px;">Karyawan</label>
              <select name="karyawan_id" class="form-control">
                <option value="0">Semua Karyawan</option>
                <?php foreach ($karyawanOptions as $opt): ?>
                  <option value="<?= $opt['id'] ?>" <?= ($karyawanId == $opt['id']) ? 'selected' : '' ?>>
                    <?= h($opt['nama']) ?> (<?= h($opt['nik']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="form-label" style="font-size: 11px;">Status Kehadiran</label>
              <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="Hadir" <?= ($statusFilter === 'Hadir') ? 'selected' : '' ?>>Hadir</option>
                <option value="Terlambat" <?= ($statusFilter === 'Terlambat') ? 'selected' : '' ?>>Terlambat</option>
                <option value="Izin" <?= ($statusFilter === 'Izin') ? 'selected' : '' ?>>Izin</option>
                <option value="Sakit" <?= ($statusFilter === 'Sakit') ? 'selected' : '' ?>>Sakit</option>
              </select>
            </div>

            <div>
              <button type="submit" class="btn btn-primary btn-block btn-sm" style="padding: 11px;">
                <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Ringkasan Statistik Laporan -->
      <div class="stats-grid" style="margin-bottom: 24px;">
        <div class="stat-card">
          <div class="stat-icon icon-green">
            <i class="fa-solid fa-check-double"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Total Hadir</div>
            <div class="stat-value"><?= (int)($summary['hadir'] ?? 0) ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-amber">
            <i class="fa-solid fa-clock-rotate-left"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Total Terlambat</div>
            <div class="stat-value"><?= (int)($summary['terlambat'] ?? 0) ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-blue">
            <i class="fa-solid fa-file-signature"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Total Izin</div>
            <div class="stat-value"><?= (int)($summary['izin'] ?? 0) ?></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-rose">
            <i class="fa-solid fa-heart-pulse"></i>
          </div>
          <div class="stat-details">
            <div class="stat-title">Total Sakit</div>
            <div class="stat-value"><?= (int)($summary['sakit'] ?? 0) ?></div>
          </div>
        </div>
      </div>

      <!-- Tabel Laporan -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa-solid fa-table-list text-accent"></i> Hasil Laporan Periode <?= date('d/m/Y', strtotime($startDate)) ?> s/d <?= date('d/m/Y', strtotime($endDate)) ?>
          </h3>
          <span style="font-size: 12px; color: var(--text-muted);"><?= $totalRows ?> rekaman ditemukan</span>
        </div>

        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Nama Karyawan</th>
                <th>NIK</th>
                <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status Kehadiran</th>
                <th>Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($laporanList)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                    <i class="fa-solid fa-file-circle-xmark" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                    Tidak ada data laporan untuk kriteria pencarian ini.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = $offset + 1; foreach ($laporanList as $lap): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= h($lap['nama']) ?></strong></td>
                    <td><span class="badge badge-secondary"><?= h($lap['nik']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($lap['tanggal'])) ?></td>
                    <td><?= !empty($lap['jam_masuk']) ? substr($lap['jam_masuk'], 0, 5) . ' WIB' : '-' ?></td>
                    <td><?= !empty($lap['jam_pulang']) ? substr($lap['jam_pulang'], 0, 5) . ' WIB' : '-' ?></td>
                    <td>
                      <span class="badge <?= ($lap['status'] === 'Hadir') ? 'badge-success' : (($lap['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
                        <?= h($lap['status']) ?>
                      </span>
                    </td>
                    <td><small class="text-muted"><?= h($lap['keterangan'] ?? '-') ?></small></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="pagination-wrapper">
            <div>
              Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalRows ?> data)
            </div>
            <ul class="pagination">
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?<?= $exportQuery ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </div>
        <?php endif; ?>

      </div>

    </main>
  </div>
</div>

<style>
@media print {
  .sidebar, .top-navbar, .pagination-wrapper, form, .btn {
    display: none !important;
  }
  .main-wrapper {
    margin-left: 0 !important;
  }
  .card {
    border: none !important;
    box-shadow: none !important;
  }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

