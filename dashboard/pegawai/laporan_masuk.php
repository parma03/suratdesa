<?php
session_start();
include '../../database/koneksi.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Administrator') {
        header("Location: dashboard/admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Masyarakat') {
        header("Location: dashboard/masyarakat/index.php");
        exit();
    }
} else {
    header("Location: ../../index.php");
    exit();
}

// Get filter parameters
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : '';
$tanggal_selesai = isset($_GET['tanggal_selesai']) ? $_GET['tanggal_selesai'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Build the query
$whereClause = "WHERE p.status_permohonan = 'diajukan'";
$params = [];

if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
    $whereClause .= " AND DATE(p.created_at) BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_selesai;
}

if (!empty($kategori)) {
    $whereClause .= " AND p.kategori_permohonan = ?";
    $params[] = $kategori;
}

// Fetch all permohonan data with user info
$stmt = $pdo->prepare("
    SELECT p.*, 
           m.nama as nama_masyarakat, m.email as email_masyarakat,
           v.nama as nama_verifikator
    FROM tb_permohonan p 
    LEFT JOIN tb_user m ON p.id_masyarakat = m.id_user 
    LEFT JOIN tb_user v ON p.id_verifikator = v.id_user 
    $whereClause
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enum values for kategori_permohonan
$enumStmt = $pdo->prepare("SHOW COLUMNS FROM tb_permohonan LIKE 'kategori_permohonan'");
$enumStmt->execute();
$enumRow = $enumStmt->fetch(PDO::FETCH_ASSOC);
$enumValues = [];
if ($enumRow) {
    $enumString = $enumRow['Type'];
    preg_match_all("/'([^']+)'/", $enumString, $matches);
    $enumValues = $matches[1];
}

// Calculate statistics
$totalPermohonan = count($data);
$kategoriStats = [];
foreach ($enumValues as $kat) {
    $kategoriStats[$kat] = array_filter($data, function ($item) use ($kat) {
        return $item['kategori_permohonan'] === $kat;
    });
    $kategoriStats[$kat] = count($kategoriStats[$kat]);
}

// Handle export to Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // Set headers for Excel download
    $filename = 'Laporan_Permohonan_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Generate period string
    $periodStr = '';
    if (!empty($tanggal_mulai) && !empty($tanggal_selesai)) {
        $periodStr = 'Periode: ' . date('d/m/Y', strtotime($tanggal_mulai)) . ' - ' . date('d/m/Y', strtotime($tanggal_selesai));
    }

    // Start Excel output
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<meta name="ProgId" content="Excel.Sheet">';
    echo '<meta name="Generator" content="Microsoft Excel 14">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
    echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
    echo '.header { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 10px; }';
    echo '.sub-header { font-size: 12px; text-align: center; margin-bottom: 20px; }';
    echo '.info { font-size: 11px; margin-bottom: 10px; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';

    // Header
    echo '<div class="header">LAPORAN DATA PERMOHONAN</div>';
    echo '<div class="sub-header">KANTOR CAMAT SUTERA</div>';
    echo '<div class="sub-header">Status: DIAJUKAN</div>';
    if ($periodStr) {
        echo '<div class="sub-header">' . $periodStr . '</div>';
    }
    if (!empty($kategori)) {
        echo '<div class="sub-header">Kategori: ' . htmlspecialchars($kategori) . '</div>';
    }
    echo '<div class="info">Dicetak pada: ' . date('d/m/Y H:i:s') . '</div>';
    echo '<div class="info">Total Data: ' . count($data) . ' permohonan</div>';
    echo '<br>';

    // Table
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>No</th>';
    echo '<th>ID Permohonan</th>';
    echo '<th>Judul Permohonan</th>';
    echo '<th>Deskripsi</th>';
    echo '<th>Nama Pemohon</th>';
    echo '<th>Email Pemohon</th>';
    echo '<th>Kategori</th>';
    echo '<th>Status</th>';
    echo '<th>Tanggal Pengajuan</th>';
    echo '<th>Verifikator</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if (count($data) > 0) {
        foreach ($data as $index => $row) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($row['id_permohonan']) . '</td>';
            echo '<td>' . htmlspecialchars($row['judul_permohonan']) . '</td>';
            echo '<td>' . htmlspecialchars($row['deskripsi permohonan'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_masyarakat']) . '</td>';
            echo '<td>' . htmlspecialchars($row['email_masyarakat']) . '</td>';
            echo '<td>' . htmlspecialchars($row['kategori_permohonan']) . '</td>';
            echo '<td>' . ucfirst(htmlspecialchars($row['status_permohonan'])) . '</td>';
            echo '<td>' . date('d/m/Y H:i:s', strtotime($row['created_at'])) . '</td>';
            echo '<td>' . htmlspecialchars($row['nama_verifikator'] ?? '-') . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="10" style="text-align: center;">Tidak ada data permohonan</td></tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Footer
    echo '<br><br>';
    echo '<div class="info">Laporan ini dibuat secara otomatis oleh Sistem Informasi Kantor Camat Sutera</div>';

    echo '</body>';
    echo '</html>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kantor Camat Sutera - Laporan Permohonan</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../../assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="../../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {
                families: ["Public Sans:300,400,500,600,700"]
            },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["../../assets/css/fonts.min.css"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../../assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <style>
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .stats-card {
            border-radius: 15px;
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .btn-export {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .btn-print {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
            color: white;
        }

        .no-print kbd {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 11px;
            color: #495057;
            font-family: monospace;
        }

        /* Enhanced button styles */
        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);
            color: white;
        }

        /* SweetAlert custom styling */
        .swal-modal {
            border-radius: 15px;
        }

        .swal-title {
            font-size: 20px;
            font-weight: 600;
        }

        .swal-text {
            font-size: 14px;
            line-height: 1.5;
        }

        .swal-button {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .swal-button:hover {
            transform: translateY(-1px);
        }

        .swal-button--confirm {
            background-color: #007bff;
        }

        .swal-button--confirm:hover {
            background-color: #0056b3;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .swal-button--cancel {
            background-color: #6c757d;
        }

        .swal-button--cancel:hover {
            background-color: #545b62;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .swal-button--danger {
            background-color: #dc3545;
        }

        .swal-button--danger:hover {
            background-color: #c82333;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Loading spinner for SweetAlert */
        .swal-icon--info {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Ganti CSS @media print yang ada dengan kode ini */
        @media print {

            /* Hide all elements by default */
            * {
                visibility: hidden;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            /* Hide completely */
            .no-print,
            .sidebar,
            .main-header,
            .navbar,
            .breadcrumb,
            .filter-card,
            .btn,
            button,
            .stats-card,
            .card-header .ms-auto .badge {
                display: none !important;
            }

            /* Show only printable content */
            #printable-content,
            #printable-content * {
                visibility: visible;
            }

            /* Position printable content */
            #printable-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 15px;
                margin: 0;
                background: white;
            }

            /* Reset page styling */
            @page {
                size: A4;
                margin: 0.5in;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                font-family: 'Times New Roman', serif !important;
                font-size: 12px;
                line-height: 1.4;
                color: #000;
            }

            /* Professional header styling */
            .print-header {
                text-align: center;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 2px solid #000;
                width: 100%;
                clear: both;
            }

            .print-header .kop-surat {
                display: table;
                width: 100%;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #000;
            }

            .print-header .logo {
                display: table-cell;
                width: 80px;
                vertical-align: middle;
                padding-right: 15px;
            }

            .print-header .logo>div {
                width: 70px;
                height: 70px;
                border: 2px solid #000;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 9px;
                text-align: center;
                font-weight: bold;
            }

            .print-header .header-text {
                display: table-cell;
                vertical-align: middle;
                text-align: center;
            }

            .print-header .header-text h1 {
                font-size: 16px;
                font-weight: bold;
                margin: 0 0 3px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .print-header .header-text h2 {
                font-size: 14px;
                margin: 0 0 5px 0;
                font-weight: bold;
            }

            .print-header .header-text .alamat {
                font-size: 10px;
                margin-top: 3px;
                font-style: italic;
                line-height: 1.3;
            }

            .print-header .report-title {
                margin-top: 15px;
                font-size: 14px;
                font-weight: bold;
                text-decoration: underline;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .print-info {
                width: 100%;
                margin: 15px 0;
                padding: 8px;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                clear: both;
                overflow: hidden;
            }

            .print-info .left-info {
                float: left;
                width: 60%;
                font-size: 10px;
                line-height: 1.4;
            }

            .print-info .right-info {
                float: right;
                width: 35%;
                text-align: right;
                font-size: 10px;
                line-height: 1.4;
            }

            /* Professional table styling */
            .print-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 15px;
                font-size: 9px;
                clear: both;
            }

            .print-table th,
            .print-table td {
                border: 1px solid #000 !important;
                padding: 4px 3px !important;
                text-align: left !important;
                vertical-align: top !important;
                word-wrap: break-word;
            }

            .print-table th {
                background-color: #e9ecef !important;
                font-weight: bold !important;
                text-align: center !important;
                font-size: 9px;
                padding: 6px 3px !important;
            }

            .print-table tbody tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }

            .print-table .text-center {
                text-align: center !important;
            }

            /* Column widths */
            .print-table .no-col {
                width: 4% !important;
                text-align: center !important;
            }

            .print-table .id-col {
                width: 10% !important;
            }

            .print-table .title-col {
                width: 28% !important;
            }

            .print-table .name-col {
                width: 15% !important;
            }

            .print-table .email-col {
                width: 18% !important;
                font-size: 8px;
            }

            .print-table .category-col {
                width: 15% !important;
                text-align: center !important;
            }

            .print-table .date-col {
                width: 10% !important;
                text-align: center !important;
                font-size: 8px;
            }

            /* Summary box styling */
            .summary-box {
                margin-top: 15px;
                border: 1px solid #000;
                padding: 8px;
                background-color: #f8f9fa;
                clear: both;
                page-break-inside: avoid;
            }

            .summary-content {
                overflow: hidden;
                font-size: 10px;
            }

            .summary-left {
                float: left;
                width: 50%;
            }

            .summary-right {
                float: right;
                width: 45%;
                text-align: right;
            }

            /* Footer styling */
            .print-footer {
                margin-top: 25px;
                clear: both;
                overflow: hidden;
                page-break-inside: avoid;
            }

            .print-footer .left-footer {
                float: left;
                width: 60%;
                font-size: 9px;
                color: #666;
                font-style: italic;
            }

            .print-footer .right-footer {
                float: right;
                width: 35%;
                text-align: center;
                font-size: 10px;
            }

            .print-footer .signature-area {
                margin-top: 10px;
                text-align: center;
            }

            .print-footer .signature-line {
                border-bottom: 1px solid #000;
                width: 150px;
                margin: 40px auto 3px;
            }

            /* Page break handling */
            .page-break-before {
                page-break-before: always;
            }

            .page-break-after {
                page-break-after: always;
            }

            .no-page-break {
                page-break-inside: avoid;
            }

            /* Remove any box shadows or rounded corners */
            * {
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div id="printable-content" style="display: none;">
        <div class="print-header">
            <div class="kop-surat">
                <div class="logo">
                    <div>LOGO<br>PEMDA</div>
                </div>
                <div class="header-text">
                    <h1>Pemerintahan Daerah Kabupaten/Kota</h1>
                    <h2>Kantor Camat Sutera</h2>
                    <div class="alamat">
                        Jl. Contoh Alamat No. 123, Sutera<br>
                        Telp: (021) 12345678 | Email: camat.sutera@pemda.go.id
                    </div>
                </div>
            </div>
            <div class="report-title">
                Laporan Data Permohonan Masyarakat
            </div>
        </div>

        <!-- Info Laporan -->
        <div class="print-info">
            <div class="left-info">
                <strong>Informasi Laporan:</strong><br>
                Status Permohonan: <strong>DIAJUKAN</strong><br>
                <?php if (!empty($tanggal_mulai) && !empty($tanggal_selesai)): ?>
                    Periode: <strong><?= date('d/m/Y', strtotime($tanggal_mulai)) ?> -
                        <?= date('d/m/Y', strtotime($tanggal_selesai)) ?></strong><br>
                <?php endif; ?>
                <?php if (!empty($kategori)): ?>
                    Kategori: <strong><?= htmlspecialchars($kategori) ?></strong><br>
                <?php endif; ?>
                Total Data: <strong><?= count($data) ?> permohonan</strong>
            </div>
            <div class="right-info">
                <strong>Tanggal Cetak:</strong><br>
                <?= date('d F Y') ?><br>
                <strong>Jam:</strong> <?= date('H:i:s') ?> WIB<br>
                <strong>Dicetak oleh:</strong><br>
                <?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Administrator' ?>
            </div>
            <div style="clear: both;"></div>
        </div>

        <!-- Tabel Data -->
        <table class="print-table">
            <thead>
                <tr>
                    <th class="no-col">No.</th>
                    <th class="id-col">ID Permohonan</th>
                    <th class="title-col">Judul Permohonan</th>
                    <th class="name-col">Nama Pemohon</th>
                    <th class="email-col">Email Pemohon</th>
                    <th class="category-col">Kategori</th>
                    <th class="date-col">Tgl. Pengajuan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($data) > 0): ?>
                    <?php foreach ($data as $index => $row): ?>
                        <tr class="<?= ($index + 1) % 15 == 0 ? 'page-break-after' : '' ?>">
                            <td class="text-center"><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($row['id_permohonan']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['judul_permohonan']) ?></strong>
                                <?php if (!empty($row['deskripsi permohonan'])): ?>
                                    <br><em style="font-size: 8px; color: #666;">
                                        <?= htmlspecialchars(substr($row['deskripsi permohonan'], 0, 60)) ?>...
                                    </em>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nama_masyarakat']) ?></td>
                            <td style="font-size: 8px;"><?= htmlspecialchars($row['email_masyarakat']) ?></td>
                            <td class="text-center">
                                <span
                                    style="background: #e9ecef; padding: 1px 3px; border: 1px solid #adb5bd; font-size: 8px; display: inline-block;">
                                    <?= htmlspecialchars($row['kategori_permohonan']) ?>
                                </span>
                            </td>
                            <td class="text-center" style="font-size: 8px;">
                                <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                                <em><?= date('H:i', strtotime($row['created_at'])) ?></em>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 15px; font-style: italic; color: #666;">
                            Tidak ada data permohonan yang memenuhi kriteria filter
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Summary Box -->
        <?php if (count($data) > 0): ?>
            <div class="summary-box">
                <strong>Ringkasan Data:</strong>
                <div class="summary-content">
                    <div class="summary-left">
                        <strong>Total Permohonan:</strong> <?= count($data) ?> permohonan<br>
                        <?php if (!empty($tanggal_mulai) && !empty($tanggal_selesai)): ?>
                            <strong>Rentang Waktu:</strong> <?= date('d F Y', strtotime($tanggal_mulai)) ?> s/d
                            <?= date('d F Y', strtotime($tanggal_selesai)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="summary-right">
                        <strong>Status:</strong> Diajukan<br>
                        <?php if (!empty($kategori)): ?>
                            <strong>Kategori:</strong> <?= htmlspecialchars($kategori) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="print-footer no-page-break">
            <div class="left-footer">
                <em>Laporan ini dibuat secara otomatis oleh Sistem Informasi Kantor Camat Sutera</em><br>
                <em>Dicetak pada: <?= date('d F Y, H:i:s') ?> WIB</em>
            </div>
            <div class="right-footer">
                <div>Sutera, <?= date('d F Y') ?></div>
                <div style="margin-top: 8px;">
                    <strong>Camat Sutera</strong>
                </div>
                <div class="signature-area">
                    <div class="signature-line"></div>
                    <div><strong>Nama Camat</strong></div>
                    <div style="font-size: 9px;">NIP. 123456789012345678</div>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
    <div class="wrapper">
        <?php include '_component/sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="index.html" class="logo">
                            <img src="../../assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand"
                                height="20" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                </div>
                <?php include '_component/navbar.php'; ?>
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Laporan Permohonan</h3>
                            <h6 class="op-7 mb-2">Laporan Data Permohonan dengan Status Diajukan</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="index.php">
                                            <i class="fa fa-home"></i> Dashboard
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        Laporan Permohonan
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Filter Card -->
                    <div class="row mb-4 no-print">
                        <div class="col-12">
                            <div class="card filter-card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        <i class="fa fa-filter"></i> Filter Laporan
                                    </h5>
                                    <form method="GET" action=""
                                        onsubmit="event.preventDefault(); submitFilterWithConfirmation();">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label class="form-label">Tanggal Mulai</label>
                                                <input type="date" class="form-control" name="tanggal_mulai"
                                                    value="<?= $tanggal_mulai ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tanggal Selesai</label>
                                                <input type="date" class="form-control" name="tanggal_selesai"
                                                    value="<?= $tanggal_selesai ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Kategori Permohonan</label>
                                                <select class="form-select" name="kategori">
                                                    <option value="">Semua Kategori</option>
                                                    <?php foreach ($enumValues as $value): ?>
                                                        <option value="<?= htmlspecialchars($value) ?>"
                                                            <?= $kategori === $value ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($value) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button type="submit" class="btn btn-light me-2">
                                                    <i class="fa fa-search"></i> Filter
                                                </button>
                                                <button type="button" onclick="resetFilterWithConfirmation()"
                                                    class="btn btn-outline-light">
                                                    <i class="fa fa-refresh"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-center">
                                        <div class="avatar avatar-lg bg-primary">
                                            <i class="fa fa-file-alt text-white fa-2x"></i>
                                        </div>
                                    </div>
                                    <h3 class="mt-3 mb-1"><?= $totalPermohonan ?></h3>
                                    <p class="text-muted mb-0">Total Permohonan</p>
                                </div>
                            </div>
                        </div>
                        <?php
                        $colors = ['success', 'warning', 'info', 'danger'];
                        $icons = ['fa-handshake', 'fa-clipboard-check', 'fa-hands-helping', 'fa-users'];
                        $i = 0;
                        foreach ($kategoriStats as $kategori => $jumlah):
                            if ($i >= 3)
                                break; // Limit to 3 additional cards
                        ?>
                            <div class="col-md-3">
                                <div class="card stats-card">
                                    <div class="card-body text-center">
                                        <div class="d-flex justify-content-center">
                                            <div class="avatar avatar-lg bg-<?= $colors[$i] ?>">
                                                <i class="fa <?= $icons[$i] ?> text-white fa-2x"></i>
                                            </div>
                                        </div>
                                        <h3 class="mt-3 mb-1"><?= $jumlah ?></h3>
                                        <p class="text-muted mb-0"><?= substr($kategori, 0, 20) ?>...</p>
                                    </div>
                                </div>
                            </div>
                        <?php
                            $i++;
                        endforeach;
                        ?>
                    </div>

                    <!-- Export Buttons -->
                    <div class="row mb-3 no-print">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button onclick="printReport()" class="btn btn-print">
                                    <i class="fa fa-print"></i> Cetak Laporan
                                </button>
                                <button onclick="exportToExcel()" class="btn btn-export">
                                    <i class="fa fa-file-excel"></i> Export Excel
                                </button>
                                <div class="ms-auto">
                                    <small class="text-muted">
                                        <i class="fa fa-keyboard"></i>
                                        Shortcut: <kbd>Ctrl+P</kbd> untuk cetak, <kbd>Ctrl+E</kbd> untuk export
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Data Permohonan - Status Diajukan</h4>
                                        <div class="ms-auto">
                                            <?php if (!empty($tanggal_mulai) && !empty($tanggal_selesai)): ?>
                                                <span class="badge badge-info">
                                                    Periode: <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> -
                                                    <?= date('d/m/Y', strtotime($tanggal_selesai)) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="basic-datatables" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th width="15%">Judul</th>
                                                    <th width="20%">Pemohon</th>
                                                    <th width="15%">Email</th>
                                                    <th width="15%">Kategori</th>
                                                    <th width="15%">Tanggal Pengajuan</th>
                                                    <th width="15%">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($data) > 0): ?>
                                                    <?php foreach ($data as $index => $row): ?>
                                                        <tr>
                                                            <td><?= $index + 1 ?></td>
                                                            <td>
                                                                <strong><?= htmlspecialchars($row['judul_permohonan']) ?></strong>
                                                                <?php if (!empty($row['deskripsi permohonan'])): ?>
                                                                    <br><small
                                                                        class="text-muted"><?= htmlspecialchars(substr($row['deskripsi permohonan'], 0, 50)) ?>...</small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['nama_masyarakat']) ?></td>
                                                            <td><?= htmlspecialchars($row['email_masyarakat']) ?></td>
                                                            <td>
                                                                <span class="badge badge-primary">
                                                                    <?= htmlspecialchars($row['kategori_permohonan']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                                            <td>
                                                                <span class="badge badge-warning">
                                                                    <?= ucfirst($row['status_permohonan']) ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            <div class="py-4">
                                                                <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                                                                <h6 class="text-muted">Tidak ada data permohonan</h6>
                                                                <p class="text-muted">Belum ada data permohonan dengan
                                                                    kriteria yang dipilih</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include '_component/footer.php'; ?>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="../../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../../assets/js/core/popper.min.js"></script>
    <script src="../../assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="../../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Chart JS -->
    <script src="../../assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- jQuery Sparkline -->
    <script src="../../assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="../../assets/js/plugin/chart-circle/circles.min.js"></script>

    <!-- Datatables -->
    <script src="../../assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="../../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="../../assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="../../assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Sweet Alert -->
    <script src="../../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="../../assets/js/kaiadmin.min.js"></script>

    <!-- Kaiadmin DEMO methods, don't include it in your project! -->
    <script src="../../assets/js/setting-demo.js"></script>
    <script src="../../assets/js/demo.js"></script>
    <script>
        // Replace the existing printReport function with this enhanced version
        function printReport() {
            swal({
                title: "Cetak Laporan",
                text: "Apakah Anda yakin ingin mencetak laporan ini?",
                icon: "info",
                buttons: {
                    cancel: {
                        text: "Batal",
                        value: null,
                        visible: true,
                        className: "btn btn-danger",
                        closeModal: true,
                    },
                    confirm: {
                        text: "Ya, Cetak",
                        value: true,
                        visible: true,
                        className: "btn btn-success",
                        closeModal: true
                    }
                },
                dangerMode: false,
            }).then((willPrint) => {
                if (willPrint) {
                    // Show loading
                    swal({
                        title: "Menyiapkan Dokumen...",
                        text: "Mohon tunggu sebentar",
                        icon: "info",
                        buttons: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false,
                    });

                    // Show the printable content
                    document.getElementById('printable-content').style.display = 'block';

                    // Small delay to ensure content is rendered
                    setTimeout(function() {
                        // Close loading and print
                        swal.close();

                        // Print the page
                        window.print();

                        // Hide the printable content again after printing
                        setTimeout(function() {
                            document.getElementById('printable-content').style.display = 'none';

                            // Show success message
                            swal({
                                title: "Berhasil!",
                                text: "Dokumen telah disiapkan untuk dicetak",
                                icon: "success",
                                button: "OK",
                                timer: 2000
                            });
                        }, 1000);
                    }, 1500);
                }
            });
        }

        // Enhanced export function with SweetAlert
        function exportToExcel() {
            swal({
                title: "Export ke Excel",
                text: "Apakah Anda yakin ingin mengunduh laporan dalam format Excel?",
                icon: "info",
                buttons: {
                    cancel: {
                        text: "Batal",
                        value: null,
                        visible: true,
                        className: "btn btn-secondary",
                        closeModal: true,
                    },
                    confirm: {
                        text: "Ya, Download",
                        value: true,
                        visible: true,
                        className: "btn btn-success",
                        closeModal: true
                    }
                },
                dangerMode: false,
            }).then((willExport) => {
                if (willExport) {
                    // Show loading
                    swal({
                        title: "Menyiapkan File Excel...",
                        text: "Mohon tunggu, file sedang diproses",
                        icon: "info",
                        buttons: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false,
                    });

                    // Build the export URL with current filters
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('export', 'excel');
                    const exportUrl = '?' + urlParams.toString();

                    // Simulate processing time
                    setTimeout(function() {
                        // Redirect to export URL
                        window.location.href = exportUrl;

                        // Show success message after a short delay
                        setTimeout(function() {
                            swal({
                                title: "Download Dimulai!",
                                text: "File Excel sedang diunduh ke perangkat Anda",
                                icon: "success",
                                button: "OK",
                                timer: 3000
                            });
                        }, 500);
                    }, 1500);
                }
            });
        }

        // Filter form submission with SweetAlert confirmation
        function submitFilterWithConfirmation() {
            const form = document.querySelector('form[method="GET"]');
            const formData = new FormData(form);
            const hasFilters = Array.from(formData.values()).some(value => value.trim() !== '');

            if (hasFilters) {
                swal({
                    title: "Terapkan Filter",
                    text: "Filter akan diterapkan pada data laporan. Lanjutkan?",
                    icon: "info",
                    buttons: {
                        cancel: {
                            text: "Batal",
                            value: null,
                            visible: true,
                            className: "btn btn-secondary",
                            closeModal: true,
                        },
                        confirm: {
                            text: "Ya, Terapkan",
                            value: true,
                            visible: true,
                            className: "btn btn-primary",
                            closeModal: true
                        }
                    },
                }).then((willFilter) => {
                    if (willFilter) {
                        // Show loading
                        swal({
                            title: "Memproses Filter...",
                            text: "Mohon tunggu sebentar",
                            icon: "info",
                            buttons: false,
                            closeOnClickOutside: false,
                            closeOnEsc: false,
                        });

                        // Submit form
                        form.submit();
                    }
                });
            } else {
                swal({
                    title: "Peringatan",
                    text: "Harap isi minimal satu filter untuk melakukan pencarian",
                    icon: "warning",
                    button: "OK"
                });
            }
        }

        // Reset filter with confirmation
        function resetFilterWithConfirmation() {
            swal({
                title: "Reset Filter",
                text: "Semua filter akan dikosongkan dan data akan dimuat ulang. Lanjutkan?",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Batal",
                        value: null,
                        visible: true,
                        className: "btn btn-secondary",
                        closeModal: true,
                    },
                    confirm: {
                        text: "Ya, Reset",
                        value: true,
                        visible: true,
                        className: "btn btn-warning",
                        closeModal: true
                    }
                },
                dangerMode: false,
            }).then((willReset) => {
                if (willReset) {
                    // Show loading
                    swal({
                        title: "Mereset Filter...",
                        text: "Memuat ulang data",
                        icon: "info",
                        buttons: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false,
                        timer: 1500
                    });

                    setTimeout(function() {
                        window.location.href = window.location.pathname;
                    }, 1500);
                }
            });
        }

        // Enhanced keyboard shortcut with SweetAlert info
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();

                swal({
                    title: "Shortcut Keyboard",
                    text: "Anda menekan Ctrl+P. Pilih metode pencetakan:",
                    icon: "info",
                    buttons: {
                        current: {
                            text: "Cetak di Tab Ini",
                            value: "current",
                            visible: true,
                            className: "btn btn-primary",
                        },
                        new: {
                            text: "Cetak di Tab Baru",
                            value: "new",
                            visible: true,
                            className: "btn btn-success",
                        },
                        cancel: {
                            text: "Batal",
                            value: null,
                            visible: true,
                            className: "btn btn-secondary",
                        }
                    },
                }).then((choice) => {
                    if (choice === "current") {
                        printReport();
                    } else if (choice === "new") {
                        printReportNewWindow();
                    }
                });
            }

            // Ctrl+E for export
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportToExcel();
            }
        });

        // Show notification when page loads if there are filters applied
        $(document).ready(function() {
            // Initialize DataTable
            $('#basic-datatables').DataTable({
                "pageLength": 10,
                "searching": true,
                "paging": true,
                "info": true,
                "ordering": true,
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Data tidak ditemukan",
                    "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    "infoEmpty": "Tidak ada data tersedia",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                }
            });

            // Check if filters are applied and show notification
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilters = urlParams.has('tanggal_mulai') || urlParams.has('tanggal_selesai') || urlParams.has('kategori');

            if (hasFilters) {
                setTimeout(function() {
                    swal({
                        title: "Filter Aktif!",
                        text: "Laporan sedang menampilkan data dengan filter yang telah diterapkan",
                        icon: "info",
                        button: "OK",
                        timer: 3000
                    });
                }, 500);
            }

            // Show welcome message when no data
            const tableRows = $('#basic-datatables tbody tr').length;
            if (tableRows === 1 && $('#basic-datatables tbody tr td').length === 1) {
                setTimeout(function() {
                    swal({
                        title: "Tidak Ada Data",
                        text: "Belum ada data permohonan yang sesuai dengan filter yang dipilih. Coba ubah kriteria pencarian.",
                        icon: "info",
                        button: "OK"
                    });
                }, 1000);
            }
        });
    </script>
</body>

</html>