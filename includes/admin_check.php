<?php
/**
 * Admin Middleware - Memastikan Hanya Pengguna Role Admin yang Boleh Mengakses
 */

require_once __DIR__ . '/auth_check.php';

if (!is_admin()) {
    set_flash('error', 'Akses ditolak! Anda tidak memiliki izin untuk membuka halaman Administrator.');
    header('Location: ' . base_url('karyawan/index.php'));
    exit;
}

