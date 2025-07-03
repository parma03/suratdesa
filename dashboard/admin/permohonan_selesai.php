<?php
session_start();
include '../../database/koneksi.php';
// Import PHPMailer classes
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Pegawai') {
        header("Location: dashboard/pegawai/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Masyarakat') {
        header("Location: dashboard/masyarakat/index.php");
        exit();
    }
} else {
    header("Location: ../../index.php");
    exit();
}

// Fungsi lengkap untuk mengirim email dengan PHPMailer
function sendEmailNotification($to_email, $nama_masyarakat, $judul_permohonan, $nama_verifikator)
{
    // Load konfigurasi email
    $emailConfig = include '../../config/email_config.php';
    $activeProvider = $emailConfig['active_provider'];
    $config = $emailConfig[$activeProvider];

    try {
        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];

        if ($config['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else if ($config['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->Port = $config['port'];

        // Set charset
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $nama_masyarakat);
        $mail->addReplyTo('info@camatssutera.go.id', 'Kantor Camat Sutera');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Permohonan Anda Telah Diverifikasi - Kantor Camat Sutera';

        // HTML message
        $message = buildEmailTemplate($nama_masyarakat, $judul_permohonan, $nama_verifikator);
        $mail->Body = $message;

        // Alternative plain text version
        $mail->AltBody = buildPlainTextEmail($nama_masyarakat, $judul_permohonan, $nama_verifikator);

        // Send email
        $mail->send();

        // Log successful email
        logEmailActivity($to_email, $nama_masyarakat, $judul_permohonan, $nama_verifikator, 'SUCCESS', 'Email sent successfully');

        return [
            'success' => true,
            'message' => 'Email berhasil dikirim ke ' . $to_email
        ];

    } catch (Exception $e) {
        // Log error
        $errorMessage = $e->getMessage();
        if (isset($mail)) {
            $errorMessage .= ' | SMTP Error: ' . $mail->ErrorInfo;
        }

        logEmailActivity($to_email, $nama_masyarakat, $judul_permohonan, $nama_verifikator, 'ERROR', $errorMessage);

        return [
            'success' => false,
            'message' => 'Gagal mengirim email: ' . $e->getMessage()
        ];
    }
}

// Fungsi untuk membuat template HTML email
function buildEmailTemplate($nama_masyarakat, $judul_permohonan, $nama_verifikator)
{
    $template = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Notifikasi Verifikasi Permohonan</title>
        <style>
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
                background-color: #f4f4f4;
            }
            .email-container { 
                max-width: 600px; 
                margin: 20px auto; 
                background-color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #007bff, #0056b3);
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
            }
            .header p {
                margin: 5px 0 0 0;
                font-size: 14px;
                opacity: 0.9;
            }
            .content { 
                padding: 30px 20px;
                background-color: #ffffff;
            }
            .greeting {
                font-size: 18px;
                color: #333;
                margin-bottom: 20px;
            }
            .info-box {
                background-color: #e8f4fd;
                border-left: 4px solid #007bff;
                padding: 20px;
                margin: 20px 0;
                border-radius: 0 4px 4px 0;
            }
            .status-badge { 
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white; 
                padding: 8px 16px; 
                border-radius: 20px; 
                font-size: 12px; 
                font-weight: bold;
                text-transform: uppercase;
                display: inline-block;
            }
            .detail-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0;
                background-color: #fff;
            }
            .detail-table td { 
                padding: 12px 15px; 
                border-bottom: 1px solid #eee;
                vertical-align: top;
            }
            .detail-table .label { 
                background-color: #f8f9fa; 
                font-weight: 600;
                color: #495057;
                width: 35%;
            }
            .next-steps {
                background-color: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
            }
            .next-steps h4 {
                color: #856404;
                margin-top: 0;
            }
            .next-steps ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            .next-steps li {
                margin: 8px 0;
                color: #856404;
            }
            .footer { 
                background-color: #343a40; 
                color: #fff; 
                padding: 25px 20px; 
                text-align: center; 
            }
            .footer-info {
                margin: 10px 0;
                font-size: 13px;
                line-height: 1.8;
            }
            .footer-note {
                font-size: 11px;
                opacity: 0.8;
                margin-top: 15px;
                font-style: italic;
            }
            .logo {
                background-color: #fff;
                color: #007bff;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <div class="logo">KC</div>
                <h1>Kantor Camat Sutera</h1>
                <p>Notifikasi Verifikasi Permohonan</p>
            </div>
            
            <div class="content">
                <div class="greeting">
                    Yth. <strong>' . htmlspecialchars($nama_masyarakat) . '</strong>,
                </div>
                
                <div class="info-box">
                    <p>Kami dengan senang hati memberitahukan bahwa permohonan Anda telah <strong>DIVERIFIKASI</strong> oleh petugas kami.</p>
                </div>
                
                <h3 style="color: #007bff; margin-bottom: 15px;">📋 Detail Permohonan</h3>
                <table class="detail-table">
                    <tr>
                        <td class="label">Judul Permohonan</td>
                        <td>' . htmlspecialchars($judul_permohonan) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td><span class="status-badge">✓ Diverifikasi</span></td>
                    </tr>
                    <tr>
                        <td class="label">Diverifikasi oleh</td>
                        <td>' . htmlspecialchars($nama_verifikator) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal Verifikasi</td>
                        <td>' . date('d F Y, H:i') . ' WIB</td>
                    </tr>
                </table>
                
                <div class="next-steps">
                    <h4>🚀 Langkah Selanjutnya</h4>
                    <ul>
                        <li>Permohonan Anda akan diproses lebih lanjut oleh tim yang berwenang</li>
                        <li>Anda dapat mengambil surat permohonan langsung ke kantor kami</li>
                        <li>Untuk informasi lebih lanjut, silakan hubungi kantor kami</li>
                    </ul>
                </div>
                
                <p style="margin-top: 30px; color: #666;">
                    Terima kasih atas kepercayaan Anda kepada <strong>Kantor Camat Sutera</strong>.
                </p>
            </div>
            
            <div class="footer">
                <div class="footer-info">
                    <strong>Kantor Camat Sutera</strong><br>
                    📍 Jl. Contoh No. 123, Kecamatan Sutera<br>
                    📞 Telp: (021) 12345678<br>
                    ✉️ Email: info@camatssutera.go.id
                </div>
                <div class="footer-note">
                    Email ini dikirim secara otomatis, mohon tidak membalas email ini.
                </div>
            </div>
        </div>
    </body>
    </html>';

    return $template;
}

// Fungsi untuk membuat versi plain text email
function buildPlainTextEmail($nama_masyarakat, $judul_permohonan, $nama_verifikator)
{
    return "
KANTOR CAMAT SUTERA
Notifikasi Verifikasi Permohonan

Yth. $nama_masyarakat,

Kami dengan senang hati memberitahukan bahwa permohonan Anda telah DIVERIFIKASI oleh petugas kami.

DETAIL PERMOHONAN:
- Judul Permohonan: $judul_permohonan
- Status: DIVERIFIKASI
- Diverifikasi oleh: $nama_verifikator
- Tanggal Verifikasi: " . date('d F Y, H:i') . " WIB

LANGKAH SELANJUTNYA:
- Permohonan Anda akan diproses lebih lanjut oleh tim yang berwenang
- Anda dapat mengambil surat permohonan langsung ke kantor kami
- Untuk informasi lebih lanjut, silakan hubungi kantor kami

Terima kasih atas kepercayaan Anda kepada Kantor Camat Sutera.

---
KANTOR CAMAT SUTERA
Jl. Contoh No. 123, Kecamatan Sutera
Telp: (021) 12345678
Email: info@camatssutera.go.id

Email ini dikirim secara otomatis, mohon tidak membalas email ini.
    ";
}

// Fungsi untuk logging aktivitas email
function logEmailActivity($to_email, $nama_masyarakat, $judul_permohonan, $nama_verifikator, $status, $message)
{
    $logMessage = "=== EMAIL NOTIFICATION LOG ===\n";
    $logMessage .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $logMessage .= "To: " . $to_email . "\n";
    $logMessage .= "Nama Masyarakat: " . $nama_masyarakat . "\n";
    $logMessage .= "Judul Permohonan: " . $judul_permohonan . "\n";
    $logMessage .= "Verifikator: " . $nama_verifikator . "\n";
    $logMessage .= "Status: " . $status . "\n";
    $logMessage .= "Message: " . $message . "\n";
    $logMessage .= "================================\n\n";

    // Save to log file
    $logFile = '../../logs/email_notifications.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Fungsi untuk test koneksi email
function testEmailConnection()
{
    $emailConfig = include '../../config/email_config.php';
    $activeProvider = $emailConfig['active_provider'];
    $config = $emailConfig[$activeProvider];

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];

        if ($config['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else if ($config['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->Port = $config['port'];

        // Test connection
        $mail->smtpConnect();
        $mail->smtpClose();

        return [
            'success' => true,
            'message' => 'Koneksi email berhasil!'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Koneksi email gagal: ' . $e->getMessage()
        ];
    }
}

// Handle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'send_email':
                $id = $_POST['id_permohonan'];

                // Get permohonan data with user info
                $stmt = $pdo->prepare("
                    SELECT p.*, 
                        m.nama as nama_masyarakat, m.email as email_masyarakat,
                        v.nama as nama_verifikator
                    FROM tb_permohonan p 
                    LEFT JOIN tb_user m ON p.id_masyarakat = m.id_user 
                    LEFT JOIN tb_user v ON p.id_verifikator = v.id_user 
                    WHERE p.id_permohonan = ?
                ");
                $stmt->execute([$id]);
                $permohonan = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($permohonan) {
                    // Cek apakah email tersedia
                    if (empty($permohonan['email_masyarakat'])) {
                        echo json_encode(['status' => 'error', 'message' => 'Email masyarakat tidak tersedia!']);
                        break;
                    }

                    // Kirim email notifikasi
                    $emailSent = sendEmailNotification(
                        $permohonan['email_masyarakat'],
                        $permohonan['nama_masyarakat'],
                        $permohonan['judul_permohonan'],
                        $permohonan['nama_verifikator'] ?? 'Admin'
                    );

                    if ($emailSent) {
                        echo json_encode([
                            'status' => 'success',
                            'message' => 'Email notifikasi berhasil dikirim ke ' . $permohonan['email_masyarakat']
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim email notifikasi!']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Data permohonan tidak ditemukan!']);
                }
                break;

            case 'delete':
                $id = $_POST['id_permohonan'];

                // Get files to delete
                $stmt = $pdo->prepare("SELECT file_permohonan FROM tb_file_permohonan WHERE id_permohonan = ?");
                $stmt->execute([$id]);
                $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Delete files from server
                foreach ($files as $file) {
                    if ($file['file_permohonan']) {
                        $filePath = '../../assets/files/' . $file['file_permohonan'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }

                // Delete files from database
                $stmt = $pdo->prepare("DELETE FROM tb_file_permohonan WHERE id_permohonan = ?");
                $stmt->execute([$id]);

                // Delete permohonan
                $stmt = $pdo->prepare("DELETE FROM tb_permohonan WHERE id_permohonan = ?");
                $result = $stmt->execute([$id]);

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Permohonan berhasil dihapus!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus permohonan.']);
                }
                break;

            case 'get_detail':
                $id = $_POST['id_permohonan'];

                // Get permohonan data with user info
                $stmt = $pdo->prepare("
                    SELECT p.*, 
                           m.nama as nama_masyarakat, m.email as email_masyarakat,
                           v.nama as nama_verifikator
                    FROM tb_permohonan p 
                    LEFT JOIN tb_user m ON p.id_masyarakat = m.id_user 
                    LEFT JOIN tb_user v ON p.id_verifikator = v.id_user 
                    WHERE p.id_permohonan = ?
                ");
                $stmt->execute([$id]);
                $permohonan = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($permohonan) {
                    // Get files
                    $stmt = $pdo->prepare("SELECT * FROM tb_file_permohonan WHERE id_permohonan = ?");
                    $stmt->execute([$id]);
                    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $permohonan['files'] = $files;
                    echo json_encode(['status' => 'success', 'data' => $permohonan]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan!']);
                }
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch all permohonan data with user info
$stmt = $pdo->prepare("
    SELECT p.*, 
           m.nama as nama_masyarakat, m.email as email_masyarakat,
           v.nama as nama_verifikator
    FROM tb_permohonan p 
    LEFT JOIN tb_user m ON p.id_masyarakat = m.id_user 
    LEFT JOIN tb_user v ON p.id_verifikator = v.id_user 
    WHERE p.status_permohonan = 'diverifikasi'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kantor Camat Sutera - Data Permohonan</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../../assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="../../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons",
                ],
                urls: ["../../assets/css/fonts.min.css"],
            },
            active: function () {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../../assets/css/kaiadmin.min.css" />

    <!-- CSS Just for demo purpose, don't include it in your project -->
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-diajukan {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-diverifikasi {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #74c0fc;
        }

        .status-selesai {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #51cf66;
        }

        .kategori-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 500;
        }

        /* Custom notification styles */
        .notification-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3) !important;
        }

        .notification-error {
            background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
            color: white !important;
            border: none !important;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3) !important;
        }

        .notification-success .btn-close,
        .notification-error .btn-close {
            filter: invert(1);
        }

        .file-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .file-item {
            padding: 8px 12px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-item:hover {
            background-color: #e9ecef;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include '_component/sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
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
                    <!-- End Logo Header -->
                </div>
                <?php include '_component/navbar.php'; ?>
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Permohonan</h3>
                            <h6 class="op-7 mb-2">Kelola Data Permohonan</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <!-- Breadcrumb -->
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="index.php">
                                            <i class="fa fa-home"></i> Dashboard
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        Permohonan
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Data Permohonan</h4>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="permohonanTable" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Judul</th>
                                                    <th>Kategori</th>
                                                    <th>Pemohon</th>
                                                    <th>Status</th>
                                                    <th>Tanggal</th>
                                                    <th>Verifikator</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1;
                                                foreach ($data as $row): ?>
                                                    <tr data-status="<?= $row['status_permohonan'] ?>">
                                                        <td><?= $no++ ?></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($row['judul_permohonan']) ?></strong>
                                                            <?php if ($row['deskripsi permohonan']): ?>
                                                                <br><small
                                                                    class="text-muted"><?= htmlspecialchars(substr($row['deskripsi permohonan'], 0, 50)) ?>...</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="kategori-badge"><?= $row['kategori_permohonan'] ?></span>
                                                        </td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($row['nama_masyarakat']) ?></strong>
                                                            <br><small
                                                                class="text-muted"><?= htmlspecialchars($row['email_masyarakat']) ?></small>
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="status-badge status-<?= $row['status_permohonan'] ?>">
                                                                <?= ucfirst($row['status_permohonan']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                                        <td>
                                                            <?= $row['nama_verifikator'] ? htmlspecialchars($row['nama_verifikator']) : '-' ?>
                                                        </td>
                                                        <td>
                                                            <div class="form-button-action">
                                                                <button type="button" class="btn btn-link btn-info btn-lg"
                                                                    onclick="viewDetail(<?= $row['id_permohonan'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Lihat Detail">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-link btn-success btn-lg"
                                                                    onclick="sendEmailNotification(<?= $row['id_permohonan'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Kirim Email Notifikasi">
                                                                    <i class="fa fa-envelope"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-link btn-danger btn-lg"
                                                                    onclick="deleteData(<?= $row['id_permohonan'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Hapus">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
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
        let dataTable;

        $(document).ready(function () {
            // Initialize DataTable
            dataTable = $('#permohonanTable').DataTable({
                "pageLength": 10,
                "searching": true,
                "paging": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
                }
            });
        });

        // View Detail
        function viewDetail(id) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'get_detail',
                    id_permohonan: id
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        const permohonan = response.data;

                        let filesHtml = '';
                        if (permohonan.files && permohonan.files.length > 0) {
                            filesHtml = '<div class="file-list">';
                            permohonan.files.forEach(function (file, index) {
                                filesHtml += `
                                    <div class="file-item">
                                        <span><i class="fa fa-file"></i> ${file.file_permohonan}</span>
                                        <a href="../../assets/files/${file.file_permohonan}" 
                                           target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-download"></i> Download
                                        </a>
                                    </div>
                                `;
                            });
                            filesHtml += '</div>';
                        } else {
                            filesHtml = '<p class="text-muted">Tidak ada file dilampirkan</p>';
                        }

                        const statusClass = `status-${permohonan.status_permohonan}`;

                        swal({
                            title: 'Detail Permohonan',
                            content: {
                                element: "div",
                                attributes: {
                                    innerHTML: additionalCSS + `
                                <div class="detail-container">
                                    <table class="table table-bordered">
                                        <tr>
                                            <td width="150"><strong>Judul</strong></td>
                                            <td>${permohonan.judul_permohonan}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Kategori</strong></td>
                                            <td><span class="kategori-badge">${permohonan.kategori_permohonan}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status</strong></td>
                                            <td><span class="status-badge ${statusClass}">${permohonan.status_permohonan.toUpperCase()}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Pemohon</strong></td>
                                            <td>${permohonan.nama_masyarakat}<br><small class="text-muted">${permohonan.email_masyarakat}</small></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Verifikator</strong></td>
                                            <td>${permohonan.nama_verifikator || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Dibuat</strong></td>
                                            <td>${new Date(permohonan.created_at).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</td>
                                        </tr>
                                        ${permohonan.updated_at ? `
                                        <tr>
                                            <td><strong>Terakhir Update</strong></td>
                                            <td>${new Date(permohonan.updated_at).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</td>
                                        </tr>
                                        ` : ''}
                                    </table>
                                    
                                    ${permohonan['deskripsi permohonan'] ? `
                                    <div class="mb-3">
                                        <h6><strong>Deskripsi Permohonan:</strong></h6>
                                        <div class="p-3 bg-light rounded">
                                            ${permohonan['deskripsi permohonan']}
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    <div class="mb-3">
                                        <h6><strong>File Permohonan:</strong></h6>
                                        ${filesHtml}
                                    </div>
                                </div>
                            `
                                }
                            },
                            button: {
                                text: 'Tutup',
                                className: 'btn btn-primary'
                            }
                        });
                    } else {
                        showNotification('error', response.message);
                    }
                }
            });
        }

        // Delete Data
        function deleteData(id) {
            swal({
                title: 'Hapus Permohonan',
                text: 'Apakah Anda yakin ingin menghapus permohonan ini? Semua file yang terkait juga akan dihapus.',
                icon: 'warning',
                buttons: {
                    cancel: {
                        visible: true,
                        text: 'Batal',
                        className: 'btn btn-secondary'
                    },
                    confirm: {
                        text: 'Hapus',
                        className: 'btn btn-danger'
                    }
                }
            }).then((result) => {
                if (result) {
                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            id_permohonan: id
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                showNotification('success', response.message);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function () {
                            showNotification('error', 'Gagal menghapus permohonan');
                        }
                    });
                }
            });
        }

        function sendEmailNotification(id) {
            swal({
                title: 'Kirim Email Notifikasi',
                text: 'Apakah Anda yakin ingin mengirim email notifikasi kepada pemohon?',
                icon: 'info',
                buttons: {
                    cancel: {
                        visible: true,
                        text: 'Batal',
                        className: 'btn btn-secondary'
                    },
                    confirm: {
                        text: 'Kirim Email',
                        className: 'btn btn-success'
                    }
                }
            }).then((result) => {
                if (result) {
                    // Show loading
                    swal({
                        title: 'Mengirim Email...',
                        text: 'Mohon tunggu sebentar',
                        icon: 'info',
                        buttons: false,
                        closeOnClickOutside: false,
                        closeOnEsc: false
                    });

                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: {
                            action: 'send_email',
                            id_permohonan: id
                        },
                        dataType: 'json',
                        success: function (response) {
                            swal.close(); // Close loading modal

                            if (response.status === 'success') {
                                showNotification('success', response.message);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            swal.close(); // Close loading modal
                            showNotification('error', 'Terjadi kesalahan saat mengirim email: ' + error);
                        }
                    });
                }
            });
        }

        // Show Notification Function
        function showNotification(type, message) {
            let notificationClass = type === 'success' ? 'notification-success' : 'notification-error';
            let icon = type === 'success' ? 'fa fa-check' : 'fa fa-times';

            $.notify({
                icon: icon,
                title: type === 'success' ? 'Berhasil!' : 'Error!',
                message: message,
            }, {
                type: type,
                placement: {
                    from: "top",
                    align: "right"
                },
                time: 3000,
                animate: {
                    enter: 'animated fadeInDown',
                    exit: 'animated fadeOutUp'
                },
                template: `<div data-notify="container" class="col-xxl-3 col-xl-3 col-lg-3 col-sm-3 col-12 alert ${notificationClass}" role="alert">
                    <button type="button" aria-hidden="true" class="btn-close" data-notify="dismiss"></button>
                    <span data-notify="icon"></span>
                    <span data-notify="title">{1}</span>
                    <span data-notify="message">{2}</span>
                    <div class="progress" data-notify="progressbar">
                        <div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                    </div>
                    <a href="{3}" target="{4}" data-notify="url"></a>
                </div>`
            });
        }

        // Additional CSS for modal detail
        const additionalCSS = `
            <style>
                .detail-container {
                    text-align: left;
                    max-height: 70vh;
                    overflow-y: auto;
                }
                
                .detail-container table {
                    margin-bottom: 1rem;
                }
                
                .detail-container .status-badge {
                    padding: 6px 12px;
                    border-radius: 15px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                }

                .detail-container .status-diajukan {
                    background-color: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeaa7;
                }

                .detail-container .status-diverifikasi {
                    background-color: #d1ecf1;
                    color: #0c5460;
                    border: 1px solid #74c0fc;
                }

                .detail-container .status-selesai {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #51cf66;
                }

                .detail-container .kategori-badge {
                    background-color: #e9ecef;
                    color: #495057;
                    padding: 4px 8px;
                    border-radius: 10px;
                    font-size: 10px;
                    font-weight: 500;
                }

                .detail-container .file-list {
                    max-height: 200px;
                    overflow-y: auto;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                    padding: 10px;
                }

                .detail-container .file-item {
                    padding: 8px 12px;
                    margin-bottom: 5px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .detail-container .file-item:hover {
                    background-color: #e9ecef;
                }

                .detail-container .file-item:last-child {
                    margin-bottom: 0;
                }
            </style>
        `;

        // Initialize tooltips
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // Refresh table data without page reload
        function refreshTable() {
            location.reload();
        }

        // Export functions for global access
        window.viewDetail = viewDetail;
        window.sendEmailNotification = sendEmailNotification;
        window.completePermohonan = completePermohonan;
        window.deleteData = deleteData;
        window.showNotification = showNotification;
        window.refreshTable = refreshTable;
    </script>
</body>

</html>