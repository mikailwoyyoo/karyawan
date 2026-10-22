<?php
/**
 * Admin - Hapus Data Karyawan
 */
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../api/fonnte.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Metode permintaan tidak sah.');
    header('Location: ' . base_url('admin/karyawan.php'));
    exit;
}

verify_csrf_token();

$id = (int)($_GET['id'] ?? 0);
$currentUser = current_user();

if ($id === (int)$currentUser['id']) {
    set_flash('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan saat ini.');
    header('Location: ' . base_url('admin/karyawan.php'));
    exit;
}

$db = getDB();

// 1. Ambil data karyawan terlebih dahulu sebelum dihapus
$stmt = $db->prepare("SELECT * FROM karyawan WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$karyawan = $stmt->fetch();

if (!$karyawan) {
    set_flash('error', 'Data karyawan tidak ditemukan.');
    header('Location: ' . base_url('admin/karyawan.php'));
    exit;
}

try {
    // 2. Hapus data dari database (cascade akan otomatis menghapus absensi terkait jika diset)
    $stmtDel = $db->prepare("DELETE FROM karyawan WHERE id = :id");
    $deleted = $stmtDel->execute([':id' => $id]);

    if ($deleted) {
        // 3. Kirim notifikasi WhatsApp pemberitahuan penghapusan
        $waResult = sendDeleteNotificationEmployee($karyawan['nama'], $karyawan['no_hp']);

        $msg = "Karyawan '{$karyawan['nama']}' ({$karyawan['nik']}) berhasil dihapus dari sistem.";
        if ($waResult['success']) {
            $msg .= " Notifikasi WhatsApp penghapusan telah dikirim.";
        } else {
            $msg .= " (Catatan WA: " . h($waResult['message']) . ")";
        }

        set_flash('success', $msg);
    } else {
        set_flash('error', 'Gagal menghapus data karyawan dari database.');
    }
} catch (PDOException $e) {
    set_flash('error', 'Terjadi kesalahan sistem database: ' . $e->getMessage());
}

header('Location: ' . base_url('admin/karyawan.php'));
exit;

