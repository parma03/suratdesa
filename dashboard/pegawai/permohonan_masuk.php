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

// Handle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'verify':
                $id = $_POST['id_permohonan'];
                $verifikator_id = $_SESSION['id_user'];

                $stmt = $pdo->prepare("UPDATE tb_permohonan SET status_permohonan = 'diverifikasi', id_verifikator = ?, updated_at = NOW() WHERE id_permohonan = ?");
                $result = $stmt->execute([$verifikator_id, $id]);

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Permohonan berhasil diverifikasi!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal memverifikasi permohonan.']);
                }
                break;

            case 'reject':
                $id = $_POST['id_permohonan'];
                $komentar = $_POST['komentar'];
                $verifikator_id = $_SESSION['id_user'];

                // Validasi komentar
                if (empty(trim($komentar))) {
                    echo json_encode(['status' => 'error', 'message' => 'Alasan penolakan harus diisi!']);
                    break;
                }

                $stmt = $pdo->prepare("UPDATE tb_permohonan SET status_permohonan = 'ditolak', id_verifikator = ?, komentar = ?, updated_at = NOW() WHERE id_permohonan = ?");
                $result = $stmt->execute([$verifikator_id, $komentar, $id]);

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Permohonan berhasil ditolak!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menolak permohonan.']);
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

            case 'print':
                $id = $_POST['id_permohonan'];

                // Get permohonan data with user info
                $stmt = $pdo->prepare("
                    SELECT p.*, 
                        m.nama as nama_masyarakat, 
                        m.email as email_masyarakat,
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

                    // Generate HTML untuk print
                    $printHtml = generatePrintHTML($permohonan, $files);
                    echo json_encode(['status' => 'success', 'html' => $printHtml]);
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

function generatePrintHTML($permohonan, $files)
{
    $statusLabel = ucfirst($permohonan['status_permohonan']);
    $tanggalDibuat = date('d/m/Y H:i', strtotime($permohonan['created_at']));
    $tanggalUpdate = $permohonan['updated_at'] ? date('d/m/Y H:i', strtotime($permohonan['updated_at'])) : '-';

    $filesHtml = '';
    if (!empty($files)) {
        $filesHtml = '<ol>';
        foreach ($files as $file) {
            $filesHtml .= '<li>' . htmlspecialchars($file['file_permohonan']) . '</li>';
        }
        $filesHtml .= '</ol>';
    } else {
        $filesHtml = '<p style="font-style: italic; color: #666;">Tidak ada file dilampirkan</p>';
    }

    // Komentar/Alasan penolakan jika ada
    $komentarHtml = '';
    if (!empty($permohonan['komentar'])) {
        $komentarHtml = '
            <tr>
                <td>Komentar/Alasan</td>
                <td>' . nl2br(htmlspecialchars($permohonan['komentar'])) . '</td>
            </tr>
        ';
    }

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Cetak Permohonan - ' . htmlspecialchars($permohonan['judul_permohonan']) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                line-height: 1.6;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                color: #2c3e50;
            }
            .header h2 {
                margin: 5px 0 0 0;
                color: #666;
                font-weight: normal;
            }
            .content {
                margin-bottom: 30px;
            }
            .info-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .info-table td {
                padding: 8px 12px;
                border: 1px solid #ddd;
                vertical-align: top;
            }
            .info-table td:first-child {
                background-color: #f8f9fa;
                font-weight: bold;
                width: 200px;
            }
            .status-badge {
                padding: 5px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
                display: inline-block;
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
            .status-ditolak {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .kategori-badge {
                background-color: #e9ecef;
                color: #495057;
                padding: 4px 8px;
                border-radius: 8px;
                font-size: 11px;
                font-weight: 500;
            }
            .description-box {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 5px;
                padding: 15px;
                margin: 10px 0;
            }
            .files-section {
                margin-top: 20px;
            }
            .files-section h4 {
                margin-bottom: 10px;
                color: #2c3e50;
            }
            .footer {
                margin-top: 40px;
                text-align: center;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #ddd;
                padding-top: 20px;
            }
            @media print {
                body {
                    margin: 0;
                    font-size: 12px;
                }
                .header {
                    page-break-after: avoid;
                }
                .content {
                    page-break-inside: avoid;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>KANTOR CAMAT SUTERA</h1>
            <h2>Laporan Detail Permohonan</h2>
        </div>
        
        <div class="content">
            <table class="info-table">
                <tr>
                    <td>ID Permohonan</td>
                    <td>' . htmlspecialchars($permohonan['id_permohonan']) . '</td>
                </tr>
                <tr>
                    <td>Judul Permohonan</td>
                    <td><strong>' . htmlspecialchars($permohonan['judul_permohonan']) . '</strong></td>
                </tr>
                <tr>
                    <td>Kategori</td>
                    <td><span class="kategori-badge">' . htmlspecialchars($permohonan['kategori_permohonan']) . '</span></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><span class="status-badge status-' . $permohonan['status_permohonan'] . '">' . $statusLabel . '</span></td>
                </tr>
                <tr>
                    <td>Nama Pemohon</td>
                    <td>' . htmlspecialchars($permohonan['nama_masyarakat']) . '</td>
                </tr>
                <tr>
                    <td>Email Pemohon</td>
                    <td>' . htmlspecialchars($permohonan['email_masyarakat']) . '</td>
                </tr>
                <tr>
                    <td>Verifikator</td>
                    <td>' . ($permohonan['nama_verifikator'] ? htmlspecialchars($permohonan['nama_verifikator']) : 'Belum ada') . '</td>
                </tr>
                <tr>
                    <td>Tanggal Dibuat</td>
                    <td>' . $tanggalDibuat . '</td>
                </tr>
                <tr>
                    <td>Terakhir Update</td>
                    <td>' . $tanggalUpdate . '</td>
                </tr>
                ' . $komentarHtml . '
            </table>
            
            ' . (!empty($permohonan['deskripsi permohonan']) ? '
            <h4>Deskripsi Permohonan:</h4>
            <div class="description-box">
                ' . nl2br(htmlspecialchars($permohonan['deskripsi permohonan'])) . '
            </div>
            ' : '') . '
            
            <div class="files-section">
                <h4>File Permohonan:</h4>
                ' . $filesHtml . '
            </div>
        </div>
        
        <div class="footer">
            <p>Dicetak pada: ' . date('d/m/Y H:i:s') . '</p>
            <p>Kantor Camat Sutera - Sistem Informasi Permohonan</p>
        </div>
    </body>
    </html>
    ';
}

// Fetch all permohonan data with user info
$stmt = $pdo->prepare("
    SELECT p.*, 
           m.nama as nama_masyarakat, m.email as email_masyarakat,
           v.nama as nama_verifikator
    FROM tb_permohonan p 
    LEFT JOIN tb_user m ON p.id_masyarakat = m.id_user 
    LEFT JOIN tb_user v ON p.id_verifikator = v.id_user 
    WHERE p.status_permohonan = 'diajukan'
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

        .status-ditolak {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

        /* Modal styling */
        .reject-modal .modal-header {
            background-color: #dc3545;
            color: white;
        }

        .reject-modal .modal-header .btn-close {
            filter: invert(1);
        }

        .form-floating textarea {
            min-height: 120px;
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
                                                                    onclick="verifyPermohonan(<?= $row['id_permohonan'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Verifikasi">
                                                                    <i class="fa fa-check"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-link btn-danger btn-lg"
                                                                    onclick="rejectPermohonan(<?= $row['id_permohonan'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Tolak Permohonan">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-link btn-warning btn-lg"
                                                                    onclick="printPermohonan(<?= $row['id_permohonan'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Cetak Permohonan">
                                                                    <i class="fa fa-print"></i>
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

    <!-- Modal Tolak Permohonan -->
    <div class="modal fade reject-modal" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">
                        <i class="fa fa-times-circle me-2"></i>Tolak Permohonan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectForm">
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="fa fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Anda akan menolak permohonan ini. Pastikan alasan penolakan jelas dan konstruktif.
                        </div>

                        <div class="form-floating">
                            <textarea class="form-control" id="komentarTolak" name="komentar"
                                placeholder="Masukkan alasan penolakan..." required></textarea>
                            <label for="komentarTolak">Alasan Penolakan *</label>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle me-1"></i>
                                Alasan penolakan akan terlihat oleh pemohon dan akan tercatat dalam sistem.
                            </small>
                        </div>

                        <input type="hidden" id="rejectPermohonanId" name="id_permohonan">
                        <input type="hidden" name="action" value="reject">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa fa-times me-1"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-ban me-1"></i>Tolak Permohonan
                        </button>
                    </div>
                </form>
            </div>
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

        $(document).ready(function() {
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

            // Handle reject form submission
            $('#rejectForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#rejectModal').modal('hide');
                            showNotification('success', response.message);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showNotification('error', response.message);
                        }
                    },
                    error: function() {
                        showNotification('error', 'Gagal menolak permohonan');
                    }
                });
            });

            // Reset form when modal is hidden
            $('#rejectModal').on('hidden.bs.modal', function() {
                $('#rejectForm')[0].reset();
                $('#komentarTolak').removeClass('is-invalid');
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
                success: function(response) {
                    if (response.status === 'success') {
                        const permohonan = response.data;

                        let filesHtml = '';
                        if (permohonan.files && permohonan.files.length > 0) {
                            filesHtml = '<div class="file-list">';
                            permohonan.files.forEach(function(file, index) {
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

                        // Tambahkan komentar jika ada
                        let komentarHtml = '';
                        if (permohonan.komentar) {
                            komentarHtml = `
                                <div class="mb-3">
                                    <h6><strong>Komentar/Alasan:</strong></h6>
                                    <div class="p-3 bg-light rounded border-start border-danger border-3">
                                        ${permohonan.komentar}
                                    </div>
                                </div>
                            `;
                        }

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
                                    
                                    ${komentarHtml}
                                    
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

        // Verify Permohonan
        function verifyPermohonan(id) {
            swal({
                title: 'Verifikasi Permohonan',
                text: 'Apakah Anda yakin ingin memverifikasi permohonan ini?',
                icon: 'warning',
                buttons: {
                    cancel: {
                        visible: true,
                        text: 'Batal',
                        className: 'btn btn-secondary'
                    },
                    confirm: {
                        text: 'Verifikasi',
                        className: 'btn btn-success'
                    }
                }
            }).then((result) => {
                if (result) {
                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: {
                            action: 'verify',
                            id_permohonan: id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                showNotification('success', response.message);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function() {
                            showNotification('error', 'Gagal memverifikasi permohonan');
                        }
                    });
                }
            });
        }

        // Reject Permohonan
        function rejectPermohonan(id) {
            $('#rejectPermohonanId').val(id);
            $('#rejectModal').modal('show');
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
                        success: function(response) {
                            if (response.status === 'success') {
                                showNotification('success', response.message);
                                setTimeout(() => {
                                    location.reload();
                                }, 1500);
                            } else {
                                showNotification('error', response.message);
                            }
                        },
                        error: function() {
                            showNotification('error', 'Gagal menghapus permohonan');
                        }
                    });
                }
            });
        }

        function printPermohonan(id) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'print',
                    id_permohonan: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Buat window baru untuk print
                        const printWindow = window.open('', '_blank', 'width=800,height=600');
                        printWindow.document.write(response.html);
                        printWindow.document.close();

                        // Tunggu sampai content dimuat kemudian print
                        printWindow.onload = function() {
                            printWindow.print();
                            // Optional: tutup window setelah print
                            printWindow.onafterprint = function() {
                                printWindow.close();
                            };
                        };
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function() {
                    showNotification('error', 'Gagal mencetak permohonan');
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

                .detail-container .status-ditolak {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
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
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // Refresh table data without page reload
        function refreshTable() {
            location.reload();
        }

        // Export functions for global access
        window.viewDetail = viewDetail;
        window.verifyPermohonan = verifyPermohonan;
        window.rejectPermohonan = rejectPermohonan;
        window.deleteData = deleteData;
        window.printPermohonan = printPermohonan;
        window.showNotification = showNotification;
        window.refreshTable = refreshTable;
    </script>
</body>

</html>