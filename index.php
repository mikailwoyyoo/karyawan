<?php
/**
 * Root Router
 * Mengarahkan Pengguna Sesuai Status Login & Role
 */
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . base_url('admin/index.php'));
    } else {
        header('Location: ' . base_url('karyawan/index.php'));
    }
} else {
    header('Location: ' . base_url('auth/login.php'));
}
exit;

