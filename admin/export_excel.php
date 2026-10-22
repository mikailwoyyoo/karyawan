<?php
/**
 * Export Laporan Absensi ke Format Excel (CSV UTF-8)
 */
require_once __DIR__ . '/../includes/admin_check.php';

$db = getDB();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$karyawanId = (int)($_GET['karyawan_id'] ?? 0);
$statusFilter = $_GET['status'] ?? '';

$where = ["a.tanggal BETWEEN :start AND :end"];
$params = [
    ':start' => $startDate,
    ':end' => $endDate
];

if ($karyawanId > 0) {
    $where[] = "a.id_karyawan = :kid";
    $params[':kid'] = $karyawanId;
}

if (!empty($statusFilter)) {
    $where[] = "a.status = :st";
    $params[':st'] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("SELECT a.tanggal, k.nik, k.nama, k.jabatan, a.jam_masuk, a.jam_pulang, a.status, a.keterangan 
    FROM absensi a 
    JOIN karyawan k ON a.id_karyawan = k.id 
    WHERE $whereSql 
    ORDER BY a.tanggal ASC, a.jam_masuk ASC");
$stmt->execute($params);
$data = $stmt->fetchAll();

// Nama file download
$filename = "laporan_absensi_" . date('Ymd_His') . ".csv";

// Header HTTP untuk download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM agar terbaca sempurna di Microsoft Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Baris Judul
fputcsv($output, ['LAPORAN REKAPITULASI ABSENSI KARYAWAN']);
fputcsv($output, ['Periode:', $startDate . ' s/d ' . $endDate]);
fputcsv($output, ['Tanggal Unduh:', date('Y-m-d H:i:s') . ' WIB']);
fputcsv($output, []); // Baris kosong

// Header Kolom
fputcsv($output, ['No', 'Tanggal', 'NIK', 'Nama Karyawan', 'Jabatan', 'Jam Masuk', 'Jam Pulang', 'Status Kehadiran', 'Keterangan']);

// Isi data
$no = 1;
foreach ($data as $row) {
    fputcsv($output, [
        $no++,
        $row['tanggal'],
        $row['nik'],
        $row['nama'],
        $row['jabatan'],
        !empty($row['jam_masuk']) ? substr($row['jam_masuk'], 0, 5) . ' WIB' : '-',
        !empty($row['jam_pulang']) ? substr($row['jam_pulang'], 0, 5) . ' WIB' : '-',
        $row['status'],
        $row['keterangan'] ?? '-'
    ]);
}

fclose($output);
exit;

