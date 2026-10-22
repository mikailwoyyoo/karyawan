<?php
/**
 * Admin - Manajemen Data Karyawan (CRUD)
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();

// Pencarian
$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Query builder
$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(nama LIKE :s1 OR nik LIKE :s2 OR email LIKE :s3 OR jabatan LIKE :s4)";
    $searchWildcard = "%$search%";
    $params[':s1'] = $searchWildcard;
    $params[':s2'] = $searchWildcard;
    $params[':s3'] = $searchWildcard;
    $params[':s4'] = $searchWildcard;
}

if (!empty($roleFilter)) {
    $where[] = "role = :r";
    $params[':r'] = $roleFilter;
}

$whereSql = implode(' AND ', $where);

// Hitung total baris
$stmtCount = $db->prepare("SELECT COUNT(*) FROM karyawan WHERE $whereSql");
$stmtCount->execute($params);
$totalRows = $stmtCount->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Ambil data karyawan
$stmtList = $db->prepare("SELECT * FROM karyawan WHERE $whereSql ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmtList->execute($params);
$karyawanList = $stmtList->fetchAll();

$pageTitle = 'Manajemen Karyawan';
$pageHeading = 'Data Karyawan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
  <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-wrapper">
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

      <div class="card">
        <div class="card-header">
          <div>
            <h3 class="card-title"><i class="fa-solid fa-users-gear text-accent"></i> Daftar Seluruh Karyawan</h3>
            <span style="font-size: 12px; color: var(--text-muted);">Total <?= $totalRows ?> karyawan terdaftar</span>
          </div>
          <div>
            <a href="<?= base_url('admin/tambah_karyawan.php') ?>" class="btn btn-primary btn-sm">
              <i class="fa-solid fa-user-plus"></i> Tambah Karyawan Baru
            </a>
          </div>
        </div>

        <!-- Filter & Search Bar -->
        <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
          <form method="GET" action="" class="filter-bar-responsive">
            <div style="flex: 1; min-width: 240px;" class="input-group has-icon">
              <i class="fa-solid fa-magnifying-glass input-icon"></i>
              <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, jabatan, atau email..." value="<?= h($search) ?>">
            </div>

            <div style="width: 160px;">
              <select name="role" class="form-control">
                <option value="">Semua Role</option>
                <option value="karyawan" <?= ($roleFilter === 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                <option value="admin" <?= ($roleFilter === 'admin') ? 'selected' : '' ?>>Admin</option>
              </select>
            </div>

            <button type="submit" class="btn btn-accent btn-sm">
              <i class="fa-solid fa-filter"></i> Filter
            </button>

            <?php if (!empty($search) || !empty($roleFilter)): ?>
              <a href="<?= base_url('admin/karyawan.php') ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
          </form>
        </div>

        <!-- Tabel Data Karyawan -->
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama Karyawan</th>
                <th>Jabatan</th>
                <th>WhatsApp</th>
                <th>Email</th>
                <th>Role</th>
                <th>Tgl Registrasi</th>
                <th style="text-align: center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($karyawanList)): ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: 36px; color: var(--text-muted);">
                    <i class="fa-solid fa-user-slash" style="font-size: 32px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                    Tidak ditemukan data karyawan yang sesuai.
                  </td>
                </tr>
              <?php else: ?>
                <?php $no = $offset + 1; foreach ($karyawanList as $k): ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= h($k['nik']) ?></strong></td>
                    <td>
                      <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                          <?= strtoupper(substr($k['nama'], 0, 1)) ?>
                        </div>
                        <div>
                          <div style="font-weight: 600; color: var(--primary-dark);"><?= h($k['nama']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td><?= h($k['jabatan']) ?></td>
                    <td>
                      <a href="https://wa.me/<?= h($k['no_hp']) ?>" target="_blank" style="color: #059669; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="fa-brands fa-whatsapp"></i> +<?= h($k['no_hp']) ?>
                      </a>
                    </td>
                    <td><small class="text-muted"><?= h($k['email']) ?></small></td>
                    <td>
                      <span class="badge <?= ($k['role'] === 'admin') ? 'badge-primary' : 'badge-info' ?>">
                        <?= strtoupper($k['role']) ?>
                      </span>
                    </td>
                    <td><small class="text-muted"><?= date('d/m/Y', strtotime($k['created_at'])) ?></small></td>
                    <td>
                      <div class="table-actions" style="justify-content: center;">
                        <!-- Detail -->
                        <a href="<?= base_url('karyawan/detail.php?id=' . $k['id']) ?>" class="action-btn action-detail" title="Detail Karyawan">
                          <i class="fa-solid fa-eye"></i>
                        </a>

                        <!-- Edit -->
                        <a href="<?= base_url('admin/edit_karyawan.php?id=' . $k['id']) ?>" class="action-btn action-edit" title="Edit Data">
                          <i class="fa-solid fa-pen-to-square"></i>
                        </a>

                        <!-- Hapus (Modal Trigger) -->
                        <?php if ($k['id'] != current_user()['id']): ?>
                          <button type="button" class="action-btn action-delete btn-confirm-delete" 
                                  data-url="<?= base_url('admin/hapus_karyawan.php?id=' . $k['id']) ?>" 
                                  data-name="<?= h($k['nama']) ?> (<?= h($k['nik']) ?>)" 
                                  title="Hapus Karyawan">
                            <i class="fa-solid fa-trash-can"></i>
                          </button>
                        <?php endif; ?>
                      </div>
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
              Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= $totalRows ?> data)
            </div>
            <ul class="pagination">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&page=<?= $page - 1 ?>">&laquo;</a>
                </li>
              <?php endif; ?>

              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>

              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a class="page-link" href="?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&page=<?= $page + 1 ?>">&raquo;</a>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        <?php endif; ?>

      </div>

    </main>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

