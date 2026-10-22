<?php
/**
 * Halaman Logout
 */
require_once __DIR__ . '/../config/config.php';

// Hapus seluruh session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirect ke login dengan flash message
session_start();
set_flash('success', 'Anda telah berhasil keluar dari sistem.');
header('Location: ' . base_url('auth/login.php'));
exit;

