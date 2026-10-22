<?php
/**
 * Logika Absensi Pulang (Check-Out)
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
$jamPulangDisplay = date('H:i');

try {
    // 1. Ambil data absensi hari ini
    $stmtCheck = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = :uid AND tanggal = :today LIMIT 1");
    $stmtCheck->execute([':uid' => $user['id'], ':today' => $today]);
    $absensi = $stmtCheck->fetch();

    // 2. Validasi: harus sudah absen masuk terlebih dahulu
    if (!$absensi || empty($absensi['jam_masuk'])) {
        set_flash('error', 'Gagal! Anda belum melakukan absensi masuk hari ini. Silakan klik Check In terlebih dahulu.');
        header('Location: ' . base_url('karyawan/index.php'));
        exit;
    }

    // 3. Validasi: tidak boleh absen pulang dua kali
    if (!empty($absensi['jam_pulang'])) {
        set_flash('error', 'Gagal! Anda sudah melakukan absensi pulang hari ini pada pukul ' . substr($absensi['jam_pulang'], 0, 5) . ' WIB.');
        header('Location: ' . base_url('karyawan/index.php'));
        exit;
    }

    // 4. Update jam pulang di database
    $stmtUpdate = $db->prepare("UPDATE absensi SET jam_pulang = :jp, updated_at = NOW() WHERE id = :id");
    $stmtUpdate->execute([
        ':jp' => $nowTime,
        ':id' => $absensi['id']
    ]);

    // 5. Ambil data karyawan langsung dari database untuk memastikan nomor HP terkini & presisi
    $stmtUserFresh = $db->prepare("SELECT nama, nik, no_hp FROM karyawan WHERE id = :id LIMIT 1");
    $stmtUserFresh->execute([':id' => $user['id']]);
    $karyawanFresh = $stmtUserFresh->fetch();

    $targetNama = $karyawanFresh['nama'] ?? $user['nama'];
    $targetNik = $karyawanFresh['nik'] ?? $user['nik'];
    $targetHp = $karyawanFresh['no_hp'] ?? $user['no_hp'];

    $tanggalIndo = format_indo_date($today);
    $jamMasukDisplay = substr($absensi['jam_masuk'], 0, 5) . ' WIB';
    
    $waResult = sendCheckOutNotification(
        $targetNama,
        $targetNik,
        $tanggalIndo,
        $jamMasukDisplay,
        $jamPulangDisplay . ' WIB',
        $targetHp
    );

    $pesanSuccess = "Absensi pulang berhasil dicatat pukul {$jamPulangDisplay} WIB. Terima kasih atas dedikasi kerja Anda hari ini!";
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

