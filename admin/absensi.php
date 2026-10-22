<?php
/**
 * Admin - Data Absensi Seluruh Karyawan
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();

// Filter
$filterTanggal = $_GET['tanggal'] ?? date('Y-m-d');
$filterStatus = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Query builder
$where = ["1=1"];
$params = [];

if (!empty($filterTanggal)) {
    $where[] = "a.tanggal = :tgl";
    $params[':tgl'] = $filterTanggal;
}

if (!empty($filterStatus)) {
    $where[] = "a.status = :st";
    $params[':st'] = $filterStatus;
}

if (!empty($search)) {
    $where[] = "(k.nama LIKE :s1 OR k.nik LIKE :s2)";
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Hitung total baris
$stmtCount = $db->prepare("SELECT COUNT(*) FROM absensi a JOIN karyawan k ON a.id_karyawan = k.id WHERE $whereSql");
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch data
$stmtList = $db->prepare("SELECT a.*, k.nama, k.nik, k.jabatan, k.no_hp 
    FROM absensi a 
    JOIN karyawan k ON a.id_karyawan = k.id 
    WHERE $whereSql 
    ORDER BY a.tanggal DESC, a.jam_masuk DESC 
    LIMIT $limit OFFSET $offset");
$stmtList->execute($params);
$absensiList = $stmtList->fetchAll();

$pageTitle = 'Data Absensi Karyawan';
$pageHeading = 'Manajemen Absensi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      
      <?php if ($flashSuccess = get_flash('success')): ?>
        <div class="alert alert-success">
          <i class="fa-solid fa-circle-check"></i>
          <span><?= h($flashSuccess) ?></span>
          <button class="alert-close">&times;</button>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <div>
            <h3 class="card-title"><i class="fa-solid fa-calendar-check text-accent"></i> Data Kehadiran Karyawan</h3>
            <span style="font-size: 12px; color: var(--text-muted);">Daftar seluruh rekam absensi masuk dan pulang</span>
          </div>
          <div>
            <a href="<?= base_url('admin/laporan.php') ?>" class="btn btn-outline btn-sm">
              <i class="fa-solid fa-file-invoice"></i> Buka Laporan Lengkap
            </a>
          </div>
        </div>

        <!-- Filter Form -->
        <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
          <form method="GET" action="" class="filter-bar-responsive" style="align-items: flex-end;">
            <div>
              <label class="form-label" style="font-size: 11px;">Tanggal Absen</label>
              <input type="date" name="tanggal" class="form-control" value="<?= h($filterTanggal) ?>">
            </div>

            <div>
              <label class="form-label" style="font-size: 11px;">Status Kehadiran</label>
              <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="Hadir" <?= ($filterStatus === 'Hadir') ? 'selected' : '' ?>>Hadir</option>
                <option value="Terlambat" <?= ($filterStatus === 'Terlambat') ? 'selected' : '' ?>>Terlambat</option>
                <option value="Izin" <?= ($filterStatus === 'Izin') ? 'selected' : '' ?>>Izin</option>
                <option value="Sakit" <?= ($filterStatus === 'Sakit') ? 'selected' : '' ?>>Sakit</option>
              </select>
            </div>

            <div style="flex: 1; min-width: 200px;">
              <label class="form-label" style="font-size: 11px;">Cari Karyawan</label>
              <input type="text" name="search" class="form-control" placeholder="Nama atau NIK..." value="<?= h($search) ?>">
            </div>

            <div>
              <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px 16px;">
                <i class="fa-solid fa-filter"></i> Terapkan
              </button>
              <?php if (!empty($filterTanggal) || !empty($filterStatus) || !empty($search)): ?>
                <a href="<?= base_url('admin/absensi.php?tanggal=') ?>" class="btn btn-outline btn-sm" style="padding: 10px 14px;">Semua Tanggal</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- Tabel Absensi -->
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Tanggal</th>
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
              <?php if (empty($absensiList)): ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: 36px; color: var(--text-muted);">
                    <i class="fa-solid fa-calendar-xmark" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                    Tidak ada data absensi untuk filter yang dipilih.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = $offset + 1; foreach ($absensiList as $row): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= date('d/m/Y', strtotime($row['tanggal'])) ?></strong></td>
                    <td><span class="badge badge-secondary"><?= h($row['nik']) ?></span></td>
                    <td><strong><?= h($row['nama']) ?></strong></td>
                    <td><?= h($row['jabatan']) ?></td>
                    <td>
                      <?= !empty($row['jam_masuk']) ? '<span style="font-weight:600; color:var(--primary);">' . substr($row['jam_masuk'], 0, 5) . ' WIB</span>' : '-' ?>
                    </td>
                    <td>
                      <?= !empty($row['jam_pulang']) ? '<span style="font-weight:600; color:#ea580c;">' . substr($row['jam_pulang'], 0, 5) . ' WIB</span>' : '<span class="text-muted">Belum Pulang</span>' ?>
                    </td>
                    <td>
                      <span class="badge <?= ($row['status'] === 'Hadir') ? 'badge-success' : (($row['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
                        <?= h($row['status']) ?>
                      </span>
                    </td>
                    <td><small class="text-muted"><?= h($row['keterangan'] ?? '-') ?></small></td>
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
                  <a class="page-link" href="?tanggal=<?= urlencode($filterTanggal) ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </div>
        <?php endif; ?>

      </div>
    </main>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

