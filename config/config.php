<?php
/**
 * Global Configuration & Helper Utilities
 * Sistem Informasi Manajemen Karyawan & Absensi
 */

defined('BASEPATH') or define('BASEPATH', dirname(__DIR__));

// Atur Timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 hari
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';

/**
 * Base URL detection helper
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Tentukan subdirektori aplikasi dari BASEPATH relatif terhadap DOCUMENT_ROOT
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']), '/') : '';
    $appRoot = rtrim(str_replace('\\', '/', realpath(BASEPATH) ?: BASEPATH), '/');
    
    $subDir = '';
    if (!empty($docRoot) && str_starts_with($appRoot, $docRoot)) {
        $subDir = substr($appRoot, strlen($docRoot));
    } else {
        $subDir = '/karyawan';
    }
    
    $base = rtrim($protocol . $host . $subDir, '/');
    if (!empty($path)) {
        return $base . '/' . ltrim($path, '/');
    }
    return $base;
}

/**
 * HTML Escaper helper
 */
function h($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render CSRF input field
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            die('<div style="font-family:sans-serif;padding:20px;text-align:center;color:#ef4444;">
                <h3>Validasi Keamanan Gagal (CSRF Token Mismatch)</h3>
                <p>Silakan muat ulang halaman dan ulangi aksi Anda.</p>
                <a href="javascript:history.back()" style="color:#2563eb;">Kembali</a>
            </div>');
        }
    }
}

/**
 * Flash message helpers
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function has_flash($type) {
    return isset($_SESSION['flash'][$type]);
}

/**
 * Pengaturan helper dari database
 */
function get_setting($key, $default = '') {
    static $settingsCache = [];
    if (isset($settingsCache[$key])) {
        return $settingsCache[$key];
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT nilai FROM pengaturan WHERE nama_pengaturan = :k LIMIT 1");
        $stmt->execute([':k' => $key]);
        $row = $stmt->fetch();
        if ($row) {
            $settingsCache[$key] = $row['nilai'];
            return $row['nilai'];
        }
    } catch (Exception $e) {
        // Fallback default
    }
    return $default;
}

function set_setting($key, $value) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO pengaturan (nama_pengaturan, nilai) VALUES (:k, :v) ON DUPLICATE KEY UPDATE nilai = :v2");
        return $stmt->execute([':k' => $key, ':v' => $value, ':v2' => $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Auth user helpers
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'nik' => $_SESSION['user_nik'] ?? '',
        'nama' => $_SESSION['user_nama'] ?? '',
        'jabatan' => $_SESSION['user_jabatan'] ?? '',
        'no_hp' => $_SESSION['user_no_hp'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'karyawan',
        'avatar' => $_SESSION['user_avatar'] ?? null,
    ];
}

function is_admin() {
    return is_logged_in() && (($_SESSION['user_role'] ?? '') === 'admin');
}

/**
 * Format tanggal Indonesia
 */
function format_indo_date($dateStr) {
    if (empty($dateStr)) return '-';
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $time = strtotime($dateStr);
    $dayName = $days[date('w', $time)];
    $d = date('j', $time);
    $m = $months[(int)date('n', $time)];
    $y = date('Y', $time);
    
    return "$dayName, $d $m $y";
}
