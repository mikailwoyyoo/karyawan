<?php
/**
 * Database Connection using PDO
 * Sistem Informasi Manajemen Karyawan & Absensi
 */

defined('BASEPATH') or define('BASEPATH', dirname(__DIR__));

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'db_karyawan');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Mendapatkan instance koneksi PDO
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Tampilkan pesan bersahabat tanpa mengekspos kredensial
            die('<div style="font-family:sans-serif;padding:20px;margin:20px auto;max-width:600px;background:#fee2e2;border:1px solid #f87171;border-radius:8px;color:#991b1b;">
                <h3 style="margin-top:0;">Gagal Terhubung ke Database MySQL</h3>
                <p>Silakan pastikan service MySQL di XAMPP sudah berjalan dan database <strong>' . htmlspecialchars(DB_NAME) . '</strong> sudah diimport.</p>
                <small>Pesan sistem: ' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}

