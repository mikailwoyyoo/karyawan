<?php
/**
 * Admin - Riwayat Log Pengiriman WhatsApp Gateway
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();

// Filter
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$where = ["1=1"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "status = :st";
    $params[':st'] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(nomor_tujuan LIKE :s1 OR nama_penerima LIKE :s2 OR pesan LIKE :s3)";
    $params[':s1'] = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Total baris
$stmtCount = $db->prepare("SELECT COUNT(*) FROM wa_logs WHERE $whereSql");
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Data
$stmtLogs = $db->prepare("SELECT * FROM wa_logs WHERE $whereSql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmtLogs->execute($params);
$logs = $stmtLogs->fetchAll();

$pageTitle = 'Log Pengiriman WhatsApp';
$pageHeading = 'WhatsApp Gateway Logs';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="content-area">
      
      <div class="card">
        <div class="card-header">
          <div>
            <h3 class="card-title"><i class="fa-brands fa-whatsapp text-accent"></i> Riwayat Pengiriman Pesan WhatsApp</h3>
            <span style="font-size: 12px; color: var(--text-muted);">Memantau log aktivitas pengiriman Fonnte Gateway</span>
          </div>
          <div>
            <a href="<?= base_url('admin/pengaturan.php') ?>" class="btn btn-primary btn-sm">
              <i class="fa-solid fa-sliders"></i> Pengaturan Fonnte
            </a>
          </div>
        </div>

        <!-- Filter Bar -->
        <div style="padding: 16px 24px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
          <form method="GET" action="" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 240px;">
              <input type="text" name="search" class="form-control" placeholder="Cari nomor, penerima, atau kata dalam pesan..." value="<?= h($search) ?>">
            </div>

            <div style="width: 160px;">
              <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <option value="sent" <?= ($statusFilter === 'sent') ? 'selected' : '' ?>>Terkirim (Sent)</option>
                <option value="failed" <?= ($statusFilter === 'failed') ? 'selected' : '' ?>>Gagal (Failed)</option>
              </select>
            </div>

            <button type="submit" class="btn btn-accent btn-sm">
              <i class="fa-solid fa-filter"></i> Filter
            </button>

            <?php if (!empty($search) || !empty($statusFilter)): ?>
              <a href="<?= base_url('admin/wa_logs.php') ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
          </form>
        </div>

        <!-- Tabel Log -->
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Waktu Pengiriman</th>
                <th>Nomor Tujuan</th>
                <th>Nama Penerima</th>
                <th>Tipe Notifikasi</th>
                <th>Isi Pesan</th>
                <th>Status</th>
                <th>Respons API</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                    <i class="fa-solid fa-comments" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                    Belum ada catatan log pengiriman WhatsApp.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = $offset + 1; foreach ($logs as $log): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?> WIB</small></td>
                    <td>
                      <a href="https://wa.me/<?= h($log['nomor_tujuan']) ?>" target="_blank" style="color:#059669; font-weight: 500;">
                        +<?= h($log['nomor_tujuan']) ?>
                      </a>
                    </td>
                    <td><strong><?= h($log['nama_penerima'] ?: '-') ?></strong></td>
                    <td><span class="badge badge-secondary"><?= h($log['tipe_notif']) ?></span></td>
                    <td style="max-width: 250px;">
                      <div style="font-size: 12px; white-space: pre-wrap; max-height: 80px; overflow-y: auto; background: #f8fafc; padding: 6px; border-radius: 6px;">
                        <?= h($log['pesan']) ?>
                      </div>
                    </td>
                    <td>
                      <span class="badge <?= ($log['status'] === 'sent') ? 'badge-success' : 'badge-danger' ?>">
                        <?= strtoupper($log['status']) ?>
                      </span>
                    </td>
                    <td style="max-width: 200px;">
                      <small style="font-size: 11px; color: var(--text-muted); display: block; word-break: break-all;">
                        <?= h($log['response_api'] ?: '-') ?>
                      </small>
                    </td>
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
              Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalRows ?> log)
            </div>
            <ul class="pagination">
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?status=<?= urlencode($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
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

