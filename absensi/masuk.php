<?php
/**
 * Logika Absensi Masuk (Check-In)
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../api/fonnte.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('karyawan/index.php'));
    exit;
}

verify_csrf_token();

$user = current_user();
$db = getDB();

$today = date('Y-m-d');
$nowTime = date('H:i:s');
$jamDisplay = date('H:i');

try {
    // 1. Periksa apakah sudah ada absensi pada hari ini
    $stmtCheck = $db->prepare("SELECT id, jam_masuk FROM absensi WHERE id_karyawan = :uid AND tanggal = :today LIMIT 1");
    $stmtCheck->execute([':uid' => $user['id'], ':today' => $today]);
    $existing = $stmtCheck->fetch();

    if ($existing && !empty($existing['jam_masuk'])) {
        set_flash('error', 'Gagal! Anda sudah melakukan absensi masuk pada hari ini pukul ' . substr($existing['jam_masuk'], 0, 5) . ' WIB.');
        header('Location: ' . base_url('karyawan/index.php'));
        exit;
    }

    // 2. Evaluasi status Hadir atau Terlambat berdasarkan batas jam toleransi
    $toleransi = get_setting('jam_masuk_toleransi', '08:15:00');
    $status = ($nowTime <= $toleransi) ? 'Hadir' : 'Terlambat';
    $keterangan = ($status === 'Terlambat') ? 'Terlambat masuk (' . $jamDisplay . ' WIB)' : 'Tepat waktu';

    // 3. Simpan absensi masuk ke database
    if ($existing) {
        $stmtSave = $db->prepare("UPDATE absensi SET jam_masuk = :jm, status = :st, keterangan = :ket WHERE id = :id");
        $stmtSave->execute([
            ':jm' => $nowTime,
            ':st' => $status,
            ':ket' => $keterangan,
            ':id' => $existing['id']
        ]);
    } else {
        $stmtSave = $db->prepare("INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, status, keterangan, created_at) VALUES (:uid, :tgl, :jm, :st, :ket, NOW())");
        $stmtSave->execute([
            ':uid' => $user['id'],
            ':tgl' => $today,
            ':jm' => $nowTime,
            ':st' => $status,
            ':ket' => $keterangan
        ]);
    }

    // 4. Ambil data karyawan langsung dari database untuk memastikan nomor HP terkini & presisi
    $stmtUserFresh = $db->prepare("SELECT nama, nik, no_hp FROM karyawan WHERE id = :id LIMIT 1");
    $stmtUserFresh->execute([':id' => $user['id']]);
    $karyawanFresh = $stmtUserFresh->fetch();

    $targetNama = $karyawanFresh['nama'] ?? $user['nama'];
    $targetNik = $karyawanFresh['nik'] ?? $user['nik'];
    $targetHp = $karyawanFresh['no_hp'] ?? $user['no_hp'];

    $tanggalIndo = format_indo_date($today);
    $waResult = sendCheckInNotification($targetNama, $targetNik, $tanggalIndo, $jamDisplay . ' WIB', $status, $targetHp);

    $pesanSuccess = "Absensi masuk berhasil dicatat pukul {$jamDisplay} WIB (Status: {$status}).";
    if ($waResult['success']) {
        $pesanSuccess .= " Notifikasi WhatsApp terkirim ke {$targetHp}.";
    } else {
        $pesanSuccess .= " (Catatan WA: " . h($waResult['message']) . ")";
    }

    set_flash('success', $pesanSuccess);

} catch (PDOException $e) {
    set_flash('error', 'Terjadi kesalahan sistem database: ' . $e->getMessage());
}

header('Location: ' . base_url('karyawan/index.php'));
exit;

