<?php
/**
 * Halaman Riwayat Absensi Pribadi Karyawan
 */
require_once __DIR__ . '/../includes/auth_check.php';

$user = current_user();
$db = getDB();

// Filter bulan dan tahun
$filterBulan = $_GET['bulan'] ?? date('m');
$filterTahun = $_GET['tahun'] ?? date('Y');
$filterStatus = $_GET['status'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Query conditions
$where = ["id_karyawan = :uid"];
$params = [':uid' => $user['id']];

if (!empty($filterBulan) && !empty($filterTahun)) {
    $where[] = "DATE_FORMAT(tanggal, '%Y-%m') = :bulan";
    $params[':bulan'] = "$filterTahun-$filterBulan";
}

if (!empty($filterStatus)) {
    $where[] = "status = :st";
    $params[':st'] = $filterStatus;
}

$whereSql = implode(' AND ', $where);

// Total items
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM absensi WHERE $whereSql");
$stmtTotal->execute($params);
$totalRows = $stmtTotal->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch records
$stmtList = $db->prepare("SELECT * FROM absensi WHERE $whereSql ORDER BY tanggal DESC LIMIT $limit OFFSET $offset");
$stmtList->execute($params);
$riwayat = $stmtList->fetchAll();

// Statistik ringkasan bulan ini
$stmtSummary = $db->prepare("SELECT status, COUNT(*) as jml FROM absensi WHERE id_karyawan = :uid AND DATE_FORMAT(tanggal, '%Y-%m') = :bulan GROUP BY status");
$stmtSummary->execute([':uid' => $user['id'], ':bulan' => "$filterTahun-$filterBulan"]);
$summaryRaw = $stmtSummary->fetchAll();
$summary = ['Hadir' => 0, 'Terlambat' => 0, 'Izin' => 0, 'Sakit' => 0];
foreach ($summaryRaw as $row) {
    if (isset($summary[$row['status']])) {
        $summary[$row['status']] = (int)$row['jml'];
    }
}

$pageTitle = 'Riwayat Absensi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mobile-app-container">
  
  <div class="mobile-header" style="padding-bottom: 50px;">
    <div class="mobile-header-top">
      <div>
        <a href="<?= base_url('karyawan/index.php') ?>" style="color: #93c5fd; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
          <i class="fa-solid fa-arrow-left"></i> Dashboard
        </a>
        <h2 class="mobile-user-name">Riwayat Kehadiran</h2>
        <div class="mobile-user-role"><?= h($user['nama']) ?> &bull; NIK: <?= h($user['nik']) ?></div>
      </div>
      <div class="mobile-user-avatar">
        <i class="fa-solid fa-clock-rotate-left"></i>
      </div>
    </div>
  </div>

  <div style="padding: 0 20px; margin-top: -30px; position: relative; z-index: 10;">
    
    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 18px;">
      <div class="card-body" style="padding: 16px;">
        <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <div>
            <label class="form-label" style="font-size: 11px;">Bulan</label>
            <select name="bulan" class="form-control" style="padding: 8px 10px; font-size: 12px;">
              <?php for ($m = 1; $m <= 12; $m++): $mPad = str_pad($m, 2, '0', STR_PAD_LEFT); ?>
                <option value="<?= $mPad ?>" <?= ($filterBulan == $mPad) ? 'selected' : '' ?>>
                  <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label class="form-label" style="font-size: 11px;">Tahun</label>
            <select name="tahun" class="form-control" style="padding: 8px 10px; font-size: 12px;">
              <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= ($filterTahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div style="grid-column: span 2;">
            <button type="submit" class="btn btn-primary btn-block btn-sm">
              <i class="fa-solid fa-filter"></i> Terapkan Filter
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Statistik Ringkasan -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 18px;">
      <div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:10px 4px; text-align:center;">
        <div style="font-size: 16px; font-weight:700; color:#065f46;"><?= $summary['Hadir'] ?></div>
        <div style="font-size: 10px; color:#065f46; font-weight:500;">Hadir</div>
      </div>
      <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:12px; padding:10px 4px; text-align:center;">
        <div style="font-size: 16px; font-weight:700; color:#92400e;"><?= $summary['Terlambat'] ?></div>
        <div style="font-size: 10px; color:#92400e; font-weight:500;">Terlambat</div>
      </div>
      <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:12px; padding:10px 4px; text-align:center;">
        <div style="font-size: 16px; font-weight:700; color:#1e40af;"><?= $summary['Izin'] ?></div>
        <div style="font-size: 10px; color:#1e40af; font-weight:500;">Izin</div>
      </div>
      <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:10px 4px; text-align:center;">
        <div style="font-size: 16px; font-weight:700; color:#991b1b;"><?= $summary['Sakit'] ?></div>
        <div style="font-size: 10px; color:#991b1b; font-weight:500;">Sakit</div>
      </div>
    </div>

    <!-- List Data Kehadiran -->
    <div class="section-header">
      <h3 class="section-title">Daftar Kehadiran (<?= $totalRows ?> Record)</h3>
    </div>

    <?php if (empty($riwayat)): ?>
      <div class="attendance-card" style="text-align: center; color: var(--text-muted); padding: 30px 20px;">
        <i class="fa-solid fa-calendar-xmark" style="font-size: 32px; color: #cbd5e1; margin-bottom: 8px;"></i>
        <p>Tidak ada catatan absensi pada periode ini.</p>
      </div>
    <?php else: ?>
      <?php foreach ($riwayat as $item): ?>
        <div class="attendance-card">
          <div class="attendance-card-header">
            <span class="attendance-card-date"><?= format_indo_date($item['tanggal']) ?></span>
            <span class="badge <?= ($item['status'] === 'Hadir') ? 'badge-success' : (($item['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
              <?= h($item['status']) ?>
            </span>
          </div>

          <div class="attendance-card-body">
            <div class="time-slot">
              <div class="time-pin-icon">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div class="time-slot-details">
                <div class="time-slot-label">Jam Masuk</div>
                <div class="time-slot-val"><?= !empty($item['jam_masuk']) ? substr($item['jam_masuk'], 0, 5) . ' WIB' : '--:--' ?></div>
              </div>
            </div>

            <div class="time-slot">
              <div class="time-pin-icon" style="background: #e0e7ff; color: #4f46e5;">
                <i class="fa-solid fa-location-pin"></i>
              </div>
              <div class="time-slot-details">
                <div class="time-slot-label">Jam Pulang</div>
                <div class="time-slot-val"><?= !empty($item['jam_pulang']) ? substr($item['jam_pulang'], 0, 5) . ' WIB' : '--:--' ?></div>
              </div>
            </div>
          </div>
          <?php if (!empty($item['keterangan'])): ?>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 8px; padding-top: 6px; border-top: 1px dashed var(--border-color);">
              <i class="fa-regular fa-note-sticky"></i> <?= h($item['keterangan']) ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin: 20px 0;">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?bulan=<?= $filterBulan ?>&tahun=<?= $filterTahun ?>&status=<?= $filterStatus ?>&page=<?= $p ?>" class="btn btn-sm <?= ($p == $page) ? 'btn-accent' : 'btn-outline' ?>" style="min-width: 34px;">
              <?= $p ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

  </div>

  <!-- Bottom Navbar -->
  <nav class="bottom-navbar">
    <a href="<?= base_url('karyawan/index.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </a>
    <a href="<?= base_url('absensi/index.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-calendar-check"></i>
      <span>Absen</span>
    </a>
    <a href="<?= base_url('absensi/riwayat.php') ?>" class="bottom-nav-item active">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <span>Riwayat</span>
    </a>
    <a href="<?= base_url('karyawan/profil.php') ?>" class="bottom-nav-item">
      <i class="fa-solid fa-user"></i>
      <span>Profile</span>
    </a>
  </nav>

</div>

<script src="<?= base_url('assets/js/script.js') ?>"></script>
</body>
</html>

