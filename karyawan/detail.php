<?php
/**
 * Halaman Detail Karyawan
 */
require_once __DIR__ . '/../includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM karyawan WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$karyawan = $stmt->fetch();

if (!$karyawan) {
    set_flash('error', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url(is_admin() ? 'admin/karyawan.php' : 'karyawan/index.php'));
    exit;
}

// Ambil riwayat absensi
$stmtAbsen = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = :id ORDER BY tanggal DESC LIMIT 10");
$stmtAbsen->execute([':id' => $id]);
$riwayat = $stmtAbsen->fetchAll();

$pageTitle = 'Detail Karyawan: ' . $karyawan['nama'];
$pageHeading = 'Informasi Karyawan';

if (is_admin()) {
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

          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-id-badge text-accent"></i> Biodata Karyawan</h3>
              <div>
                <a href="<?= base_url('admin/edit_karyawan.php?id=' . $karyawan['id']) ?>" class="btn btn-primary btn-sm">
                  <i class="fa-solid fa-pen-to-square"></i> Edit Karyawan
                </a>
              </div>
            </div>
            <div class="card-body">
              <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
                <div style="width: 80px; height: 80px; border-radius: 20px; background: linear-gradient(135deg, #0b2863, #4f46e5); color:#fff; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700;">
                  <?= strtoupper(substr($karyawan['nama'], 0, 1)) ?>
                </div>
                <div style="flex: 1; min-width: 250px;">
                  <h2 style="font-size: 22px; margin-bottom: 4px;"><?= h($karyawan['nama']) ?></h2>
                  <div style="color: var(--accent); font-weight: 600; margin-bottom: 12px;"><?= h($karyawan['jabatan']) ?> &bull; <span class="badge badge-info"><?= strtoupper($karyawan['role']) ?></span></div>

                  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13.5px;">
                    <div><strong>NIK:</strong> <?= h($karyawan['nik']) ?></div>
                    <div><strong>Nomor WhatsApp:</strong> +<?= h($karyawan['no_hp']) ?></div>
                    <div><strong>Email:</strong> <?= h($karyawan['email']) ?></div>
                    <div><strong>Tanggal Registrasi:</strong> <?= format_indo_date($karyawan['created_at']) ?></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Riwayat Kehadiran -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title"><i class="fa-solid fa-calendar-days text-accent"></i> 10 Riwayat Absensi Terakhir</h3>
            </div>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($riwayat)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 24px; color: var(--text-muted);">Belum ada riwayat absensi untuk karyawan ini.</td></tr>
                  <?php else: ?>
                    <?php foreach ($riwayat as $r): ?>
                      <tr>
                        <td><strong><?= format_indo_date($r['tanggal']) ?></strong></td>
                        <td><?= !empty($r['jam_masuk']) ? substr($r['jam_masuk'], 0, 5) . ' WIB' : '-' ?></td>
                        <td><?= !empty($r['jam_pulang']) ? substr($r['jam_pulang'], 0, 5) . ' WIB' : '-' ?></td>
                        <td>
                          <span class="badge <?= ($r['status'] === 'Hadir') ? 'badge-success' : (($r['status'] === 'Terlambat') ? 'badge-warning' : 'badge-danger') ?>">
                            <?= h($r['status']) ?>
                          </span>
                        </td>
                        <td><?= h($r['keterangan'] ?? '-') ?></td>
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
    <?php
    require_once __DIR__ . '/../includes/footer.php';
} else {
    // Tampilan Mobile untuk Karyawan
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="mobile-app-container">
      <div class="mobile-header" style="padding-bottom: 50px;">
        <div class="mobile-header-top">
          <div>
            <a href="<?= base_url('karyawan/index.php') ?>" style="color: #93c5fd; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
              <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
            <h2 class="mobile-user-name">Detail Karyawan</h2>
            <div class="mobile-user-role"><?= h($karyawan['nama']) ?></div>
          </div>
          <div class="mobile-user-avatar">
            <?= strtoupper(substr($karyawan['nama'], 0, 1)) ?>
          </div>
        </div>
      </div>
      <div style="padding: 0 20px; margin-top: -30px;">
        <div class="card">
          <div class="card-body">
            <h3><?= h($karyawan['nama']) ?></h3>
            <p style="color:var(--text-muted); font-size:13px;"><?= h($karyawan['jabatan']) ?></p>
            <hr style="margin: 12px 0; border: none; border-top: 1px solid var(--border-color);">
            <p><strong>NIK:</strong> <?= h($karyawan['nik']) ?></p>
            <p><strong>Email:</strong> <?= h($karyawan['email']) ?></p>
            <p><strong>WhatsApp:</strong> +<?= h($karyawan['no_hp']) ?></p>
          </div>
        </div>
      </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
}
?>

