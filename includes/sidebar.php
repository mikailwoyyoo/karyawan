<?php
/**
 * Admin Sidebar Navigation
 */
require_once __DIR__ . '/../config/config.php';

$currentUri = $_SERVER['SCRIPT_NAME'];
$appName = get_setting('nama_aplikasi', 'KERJOO');

function isNavActive($pageName) {
    global $currentUri;
    return (strpos($currentUri, $pageName) !== false) ? 'active' : '';
}
?>
<!-- Mobile Backdrop Overlay -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar">
  <div class="sidebar-brand">
    <img src="<?= base_url('assets/img/logo.svg') ?>" alt="Logo" class="sidebar-logo">
    <div style="flex: 1;">
      <div class="sidebar-title"><?= h($appName) ?></div>
      <div class="sidebar-subtitle">Admin Workspace</div>
    </div>
    <!-- Mobile Close Button -->
    <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" title="Tutup Menu">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>

  <ul class="sidebar-menu">
    <li class="sidebar-label">Utama</li>
    <li class="sidebar-item <?= isNavActive('/admin/index.php') ?>">
      <a href="<?= base_url('admin/index.php') ?>" class="sidebar-link">
        <i class="fa-solid fa-chart-pie"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="sidebar-label">Manajemen</li>
    <li class="sidebar-item <?= isNavActive('/admin/scan.php') ?>">
      <a href="<?= base_url('admin/scan.php') ?>" class="sidebar-link">
        <i class="fa-solid fa-qrcode"></i>
        <span>Scan Barcode Absen</span>
      </a>
    </li>

    <li class="sidebar-item <?= (isNavActive('/admin/karyawan.php') || isNavActive('/admin/tambah_karyawan.php') || isNavActive('/admin/edit_karyawan.php')) ?>">
      <a href="<?= base_url('admin/karyawan.php') ?>" class="sidebar-link">
        <i class="fa-solid fa-users"></i>
        <span>Data Karyawan</span>
      </a>
    </li>

    <li class="sidebar-item <?= isNavActive('/admin/absensi.php') ?>">
      <a href="<?= base_url('admin/absensi.php') ?>" class="sidebar-link">
        <i class="fa-solid fa-calendar-check"></i>
        <span>Data Absensi</span>
      </a>
    </li>

    <li class="sidebar-item <?= isNavActive('/admin/laporan.php') ?>">
      <a href="<?= base_url('admin/laporan.php') ?>" class="sidebar-link">
        <i class="fa-solid fa-file-invoice"></i>
        <span>Laporan Absensi</span>
      </a>
    </li>

    <li class="sidebar-label">Konfigurasi</li>
    <li class="sidebar-item <?= isNavActive('/admin/pengaturan.php') ?>">
      <a href="<?= base_url('admin/pengaturan.php') ?>" class="sidebar-link">
        <i class="fa-solid fa-sliders"></i>
        <span>Pengaturan & WA</span>
      </a>
    </li>

    <li class="sidebar-item <?= isNavActive('/admin/wa_logs.php') ?>">
      <a href="<?= base_url('admin/wa_logs.php') ?>" class="sidebar-link">
        <i class="fa-brands fa-whatsapp"></i>
        <span>WhatsApp Logs</span>
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-outline btn-block btn-sm" style="background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.15);">
      <i class="fa-solid fa-right-from-bracket"></i>
      <span>Keluar / Logout</span>
    </a>
  </div>
</aside>

