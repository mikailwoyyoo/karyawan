<?php
/**
 * Layout Header
 */
require_once __DIR__ . '/../config/config.php';

$pageTitle = $pageTitle ?? 'Sistem Informasi Manajemen Karyawan';
$appName = get_setting('nama_aplikasi', 'KERJOO');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= h($pageTitle) ?> | <?= h($appName) ?></title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
  <link rel="shortcut icon" href="<?= base_url('assets/img/favicon.svg') ?>">
  
  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
  
  <!-- Chart.js (Conditional or Global for analytics) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

