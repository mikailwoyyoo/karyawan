<?php
/**
 * Auth Middleware - Memastikan Pengguna Sudah Login
 */

require_once __DIR__ . '/../config/config.php';

if (!is_logged_in()) {
    set_flash('error', 'Sesi Anda telah berakhir atau belum login. Silakan login terlebih dahulu.');
    header('Location: ' . base_url('auth/login.php'));
    exit;
}

