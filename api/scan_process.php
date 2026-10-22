<?php
/**
 * AJAX Endpoint - Pemrosesan Scan Barcode / QR Code Absensi
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../api/fonnte.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
    exit;
}

// Ambil input JSON atau POST
$inputRaw = file_get_contents('php://input');
$data = json_decode($inputRaw, true);
if (!$data) {
    $data = $_POST;
}

$nik = trim($data['nik'] ?? '');
$mode = trim($data['mode'] ?? 'masuk'); // 'masuk' atau 'pulang'

if (empty($nik)) {
    echo json_encode(['success' => false, 'message' => 'Barcode atau NIK tidak terbaca.']);
    exit;
}

$db = getDB();

// 1. Cari data karyawan berdasarkan NIK
$stmtKaryawan = $db->prepare("SELECT * FROM karyawan WHERE nik = :nik LIMIT 1");
$stmtKaryawan->execute([':nik' => $nik]);
$karyawan = $stmtKaryawan->fetch();

if (!$karyawan) {
    echo json_encode([
        'success' => false, 
        'message' => "Data karyawan dengan barcode/NIK '{$nik}' tidak terdaftar dalam database."
    ]);
    exit;
}

$today = date('Y-m-d');
$nowTime = date('H:i:s');
$jamDisplay = date('H:i');
$tanggalIndo = format_indo_date($today);

try {
    // Cek absensi hari ini
    $stmtAbsen = $db->prepare("SELECT * FROM absensi WHERE id_karyawan = :kid AND tanggal = :tgl LIMIT 1");
    $stmtAbsen->execute([':kid' => $karyawan['id'], ':tgl' => $today]);
    $absensi = $stmtAbsen->fetch();

    if ($mode === 'masuk') {
        // Mode Absen Masuk
        if ($absensi && !empty($absensi['jam_masuk'])) {
            echo json_encode([
                'success' => false,
                'karyawan' => [
                    'nama' => $karyawan['nama'],
                    'nik' => $karyawan['nik'],
                    'jabatan' => $karyawan['jabatan']
                ],
                'message' => "{$karyawan['nama']} sudah melakukan absensi masuk hari ini pada pukul " . substr($absensi['jam_masuk'], 0, 5) . " WIB."
            ]);
            exit;
        }

        // Tentukan Hadir atau Terlambat
        $toleransi = get_setting('jam_masuk_toleransi', '08:15:00');
        $status = ($nowTime <= $toleransi) ? 'Hadir' : 'Terlambat';
        $keterangan = ($status === 'Terlambat') ? 'Terlambat masuk via scan (' . $jamDisplay . ' WIB)' : 'Tepat waktu (Scan Barcode)';

        if ($absensi) {
            $stmtSave = $db->prepare("UPDATE absensi SET jam_masuk = :jm, status = :st, keterangan = :ket WHERE id = :id");
            $stmtSave->execute([':jm' => $nowTime, ':st' => $status, ':ket' => $keterangan, ':id' => $absensi['id']]);
        } else {
            $stmtSave = $db->prepare("INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, status, keterangan, created_at) VALUES (:kid, :tgl, :jm, :st, :ket, NOW())");
            $stmtSave->execute([':kid' => $karyawan['id'], ':tgl' => $today, ':jm' => $nowTime, ':st' => $status, ':ket' => $keterangan]);
        }

        // Kirim WhatsApp
        $waResult = sendCheckInNotification($karyawan['nama'], $karyawan['nik'], $tanggalIndo, $jamDisplay . ' WIB', $status, $karyawan['no_hp']);

        echo json_encode([
            'success' => true,
            'type' => 'masuk',
            'status' => $status,
            'jam' => $jamDisplay . ' WIB',
            'karyawan' => [
                'nama' => $karyawan['nama'],
                'nik' => $karyawan['nik'],
                'jabatan' => $karyawan['jabatan'],
                'no_hp' => $karyawan['no_hp']
            ],
            'message' => "Absensi MASUK atas nama {$karyawan['nama']} berhasil dicatat!",
            'wa_status' => $waResult['success'] ? 'Notifikasi WA terkirim' : 'WA: ' . $waResult['message']
        ]);
        exit;

    } elseif ($mode === 'pulang') {
        // Mode Absen Pulang
        if (!$absensi || empty($absensi['jam_masuk'])) {
            echo json_encode([
                'success' => false,
                'karyawan' => [
                    'nama' => $karyawan['nama'],
                    'nik' => $karyawan['nik'],
                    'jabatan' => $karyawan['jabatan']
                ],
                'message' => "Gagal! {$karyawan['nama']} belum melakukan absensi masuk hari ini."
            ]);
            exit;
        }

        if (!empty($absensi['jam_pulang'])) {
            echo json_encode([
                'success' => false,
                'karyawan' => [
                    'nama' => $karyawan['nama'],
                    'nik' => $karyawan['nik'],
                    'jabatan' => $karyawan['jabatan']
                ],
                'message' => "{$karyawan['nama']} sudah melakukan absensi pulang pada pukul " . substr($absensi['jam_pulang'], 0, 5) . " WIB."
            ]);
            exit;
        }

        // Update jam pulang
        $stmtUp = $db->prepare("UPDATE absensi SET jam_pulang = :jp, updated_at = NOW() WHERE id = :id");
        $stmtUp->execute([':jp' => $nowTime, ':id' => $absensi['id']]);

        // Kirim WhatsApp
        $jamMasukDisplay = substr($absensi['jam_masuk'], 0, 5) . ' WIB';
        $waResult = sendCheckOutNotification($karyawan['nama'], $karyawan['nik'], $tanggalIndo, $jamMasukDisplay, $jamDisplay . ' WIB', $karyawan['no_hp']);

        echo json_encode([
            'success' => true,
            'type' => 'pulang',
            'status' => 'Pulang',
            'jam' => $jamDisplay . ' WIB',
            'karyawan' => [
                'nama' => $karyawan['nama'],
                'nik' => $karyawan['nik'],
                'jabatan' => $karyawan['jabatan'],
                'no_hp' => $karyawan['no_hp']
            ],
            'message' => "Absensi PULANG atas nama {$karyawan['nama']} berhasil dicatat!",
            'wa_status' => $waResult['success'] ? 'Notifikasi WA terkirim' : 'WA: ' . $waResult['message']
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Kesalahan sistem: ' . $e->getMessage()]);
    exit;
}

