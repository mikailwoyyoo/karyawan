<?php
/**
 * Top Navbar for Admin & System
 */
require_once __DIR__ . '/../config/config.php';

$user = current_user();
?>
<header class="top-navbar">
  <div class="navbar-left">
    <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Buka/Tutup Menu">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div class="page-breadcrumb">
      <h2><?= h($pageHeading ?? 'Dashboard') ?></h2>
    </div>
  </div>

  <div class="navbar-right">
    <div style="font-size: 13px; font-weight: 500; color: var(--text-muted); display: flex; align-items: center; gap: 6px;" class="d-none d-md-flex">
      <i class="fa-regular fa-clock text-accent"></i>
      <span class="live-clock">--:--:-- WIB</span>
    </div>

    <div class="user-profile-badge">
      <div class="user-avatar">
        <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= h($user['nama'] ?? 'Pengguna') ?></div>
        <div class="user-role"><?= h($user['role'] ?? 'Role') ?></div>
      </div>
    </div>
  </div>
</header>

