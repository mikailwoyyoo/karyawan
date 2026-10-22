<?php
/**
 * Modul WhatsApp Gateway Fonnte API
 * Endpoint Resmi: https://api.fonnte.com/send
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Normalisasi format nomor telepon ke standar internasional Indonesia (62xxx)
 * @param string $phone
 * @return string
 */
function normalizePhoneNumber($phone) {
    // Hapus semua karakter selain angka dan plus
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Jika berawalan '0', ganti dengan '62'
    if (str_starts_with($clean, '0')) {
        $clean = '62' . substr($clean, 1);
    }
    // Jika berawalan '8', tambahkan '62'
    elseif (str_starts_with($clean, '8')) {
        $clean = '62' . $clean;
    }
    
    return $clean;
}

/**
 * Fungsi Pengiriman Pesan WhatsApp via Fonnte Gateway API
 * 
 * @param string $target Nomor tujuan WhatsApp
 * @param string $message Teks pesan yang akan dikirim
 * @param string $tipeNotif Kategori notifikasi untuk log
 * @param string $namaPenerima Nama penerima pesan (opsional)
 * @return array ['success' => bool, 'message' => string, 'response' => mixed]
 */
function sendWhatsApp($target, $message, $tipeNotif = 'general', $namaPenerima = '') {
    $normalizedTarget = normalizePhoneNumber($target);
    
    // Validasi nomor tujuan
    if (strlen($normalizedTarget) < 9) {
        $errorMsg = "Nomor WhatsApp tidak valid: " . h($target);
        logWhatsApp($normalizedTarget, $namaPenerima, $message, $tipeNotif, 'failed', $errorMsg);
        return [
            'success' => false,
            'message' => $errorMsg,
            'response' => null
        ];
    }

    // Ambil Token dari database pengaturan sistem atau fallback .env
    $token = trim((string)get_setting('fonnte_token', ''));
    if (empty($token) && file_exists(dirname(__DIR__) . '/.env')) {
        $envLines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (str_starts_with(trim($line), 'FONNTE_TOKEN=')) {
                $token = trim(substr(trim($line), 13), '"\' ');
                break;
            }
        }
    }
    $waActive = get_setting('wa_notif_aktif', '1');

    if ($waActive !== '1') {
        $notice = "Notifikasi WhatsApp dinonaktifkan dalam pengaturan sistem.";
        logWhatsApp($normalizedTarget, $namaPenerima, $message, $tipeNotif, 'failed', $notice);
        return [
            'success' => false,
            'message' => $notice,
            'response' => null
        ];
    }

    if (empty($token) || $token === 'YOUR_FONNTE_TOKEN_HERE') {
        $errorMsg = "Token Fonnte belum dikonfigurasi di Pengaturan Sistem.";
        logWhatsApp($normalizedTarget, $namaPenerima, $message, $tipeNotif, 'failed', $errorMsg);
        return [
            'success' => false,
            'message' => $errorMsg,
            'response' => null
        ];
    }

    // Inisialisasi cURL POST ke Fonnte
    $curl = curl_init();
    $postData = [
        'target' => $normalizedTarget,
        'message' => $message,
        'countryCode' => '62'
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            "Authorization: $token"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    // Cek kegagalan koneksi cURL / Network
    if ($curlError) {
        $errorMsg = "Koneksi cURL Gagal: " . $curlError;
        logWhatsApp($normalizedTarget, $namaPenerima, $message, $tipeNotif, 'failed', $errorMsg);
        return [
            'success' => false,
            'message' => $errorMsg,
            'response' => null
        ];
    }

    // Decode respon JSON dari Fonnte
    $responseData = json_decode($response, true);
    
    // Evaluasi status respons dari Fonnte
    $isSuccess = false;
    $resultMessage = '';

    if ($httpCode === 200 && is_array($responseData)) {
        if (!empty($responseData['status']) && $responseData['status'] === true) {
            $isSuccess = true;
            $resultMessage = 'Pesan WhatsApp berhasil dikirim via Fonnte.';
        } elseif (isset($responseData['status']) && $responseData['status'] === false) {
            $resultMessage = 'Fonnte mengembalikan error: ' . ($responseData['reason'] ?? $responseData['message'] ?? 'Device disconnect / token tidak valid.');
        } else {
            $resultMessage = 'Respons Fonnte: ' . ($responseData['message'] ?? 'Format respons tidak dikenal');
        }
    } else {
        $resultMessage = "HTTP Status {$httpCode}: " . ($response ?: 'Tidak ada balasan dari server Fonnte');
    }

    // Catat log riwayat pengiriman
    logWhatsApp(
        $normalizedTarget,
        $namaPenerima,
        $message,
        $tipeNotif,
        $isSuccess ? 'sent' : 'failed',
        $resultMessage
    );

    return [
        'success' => $isSuccess,
        'message' => $resultMessage,
        'response' => $responseData
    ];
}

/**
 * Mencatat log WhatsApp ke tabel wa_logs secara aman
 */
function logWhatsApp($target, $penerima, $message, $tipe, $status, $response) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO wa_logs (nomor_tujuan, nama_penerima, pesan, tipe_notif, status, response_api, created_at) VALUES (:no, :nama, :pesan, :tipe, :st, :resp, NOW())");
        $stmt->execute([
            ':no' => $target,
            ':nama' => $penerima,
            ':pesan' => $message,
            ':tipe' => $tipe,
            ':st' => $status,
            ':resp' => substr((string)$response, 0, 500)
        ]);
    } catch (Exception $e) {
        // Abaikan error logging agar alur utama tidak terganggu
    }
}

// -------------------------------------------------------------
// Template Khusus Notifikasi Berdasarkan Kebutuhan Aplikasi
// -------------------------------------------------------------

/**
 * 1. Notifikasi Registrasi kepada Karyawan
 */
function sendRegistrationNotificationEmployee($nama, $nik, $jabatan, $noHp) {
    $pesan = "*REGISTRASI BERHASIL*\n\n"
           . "Halo *" . $nama . "*,\n\n"
           . "Data Anda berhasil didaftarkan sebagai karyawan.\n\n"
           . "NIK: *" . $nik . "*\n"
           . "Jabatan: *" . $jabatan . "*\n\n"
           . "Silakan login menggunakan akun yang telah dibuat.\n\n"
           . "Terima kasih.";
    return sendWhatsApp($noHp, $pesan, 'registrasi_karyawan', $nama);
}

/**
 * 2. Notifikasi Registrasi kepada Admin (Opsional - default nonaktif agar tidak nyasar)
 */
function sendRegistrationNotificationAdmin($nama, $nik, $jabatan, $noHp) {
    // Hanya kirim ke admin jika opsi notifikasi admin diaktifkan
    $notifAdminAktif = get_setting('wa_notif_admin_aktif', '0');
    if ($notifAdminAktif !== '1') {
        return [
            'success' => true,
            'message' => 'Notifikasi ke nomor admin dinonaktifkan (hanya dikirim ke karyawan yang bersangkutan).',
            'response' => null
        ];
    }

    $adminWa = trim((string)get_setting('admin_whatsapp', ''));
    if (empty($adminWa)) {
        return [
            'success' => true,
            'message' => 'Nomor WhatsApp admin belum diatur.',
            'response' => null
        ];
    }

    $pesan = "*NOTIFIKASI KARYAWAN BARU*\n\n"
           . "Telah terdaftar karyawan baru.\n\n"
           . "Nama: *" . $nama . "*\n"
           . "NIK: *" . $nik . "*\n"
           . "Jabatan: *" . $jabatan . "*\n"
           . "Nomor WhatsApp: *" . $noHp . "*\n\n"
           . "Silakan buka dashboard untuk melihat data lengkap.";
    return sendWhatsApp($adminWa, $pesan, 'registrasi_admin', 'Admin');
}

/**
 * 3. Notifikasi Perubahan Data kepada Karyawan
 */
function sendUpdateNotificationEmployee($nama, $jabatan, $email, $noHp) {
    $pesan = "*PEMBARUAN DATA KARYAWAN*\n\n"
           . "Halo *" . $nama . "*,\n\n"
           . "Data karyawan Anda telah diperbarui oleh administrator.\n\n"
           . "Jabatan terbaru: *" . $jabatan . "*\n"
           . "Email terbaru: *" . $email . "*\n\n"
           . "Jika perubahan ini tidak Anda ketahui, silakan hubungi administrator.";
    return sendWhatsApp($noHp, $pesan, 'update_karyawan', $nama);
}

/**
 * 4. Notifikasi Penghapusan Data kepada Karyawan
 */
function sendDeleteNotificationEmployee($nama, $noHp) {
    $pesan = "*NOTIFIKASI DATA KARYAWAN*\n\n"
           . "Halo *" . $nama . "*,\n\n"
           . "Data Anda pada sistem karyawan telah dihapus oleh administrator.\n\n"
           . "Jika Anda merasa penghapusan ini tidak semestinya, silakan hubungi administrator.";
    return sendWhatsApp($noHp, $pesan, 'delete_karyawan', $nama);
}

/**
 * 5. Notifikasi Absensi Masuk
 */
function sendCheckInNotification($nama, $nik, $tanggal, $jam, $status, $noHp) {
    $pesan = "*ABSENSI MASUK*\n\n"
           . "Nama: *" . $nama . "*\n"
           . "NIK: *" . $nik . "*\n"
           . "Tanggal: *" . $tanggal . "*\n"
           . "Jam masuk: *" . $jam . "*\n"
           . "Status: *" . $status . "*\n\n"
           . "Absensi masuk Anda berhasil dicatat.";
    return sendWhatsApp($noHp, $pesan, 'absen_masuk', $nama);
}

/**
 * 6. Notifikasi Absensi Pulang
 */
function sendCheckOutNotification($nama, $nik, $tanggal, $jamMasuk, $jamPulang, $noHp) {
    $pesan = "*ABSENSI PULANG*\n\n"
           . "Nama: *" . $nama . "*\n"
           . "NIK: *" . $nik . "*\n"
           . "Tanggal: *" . $tanggal . "*\n"
           . "Jam masuk: *" . $jamMasuk . "*\n"
           . "Jam pulang: *" . $jamPulang . "*\n"
           . "Status: *Pulang*\n\n"
           . "Terima kasih. Absensi pulang Anda berhasil dicatat.";
    return sendWhatsApp($noHp, $pesan, 'absen_pulang', $nama);
}

