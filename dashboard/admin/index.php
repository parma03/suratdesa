<?php
session_start();
include '../../database/koneksi.php';

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

// Ambil statistik untuk dashboard
try {
    // Total pengguna berdasarkan role
    $stmt = $pdo->prepare("SELECT role, COUNT(*) as total FROM tb_user GROUP BY role");
    $stmt->execute();
    $user_stats = $stmt->fetchAll();

    // Total permohonan berdasarkan status
    $stmt = $pdo->prepare("SELECT status_permohonan, COUNT(*) as total FROM tb_permohonan GROUP BY status_permohonan");
    $stmt->execute();
    $permohonan_stats = $stmt->fetchAll();

    // Total permohonan berdasarkan kategori
    $stmt = $pdo->prepare("SELECT kategori_permohonan, COUNT(*) as total FROM tb_permohonan GROUP BY kategori_permohonan");
    $stmt->execute();
    $kategori_stats = $stmt->fetchAll();

    // Permohonan terbaru
    $stmt = $pdo->prepare("
        SELECT p.*, u.nama as nama_pemohon 
        FROM tb_permohonan p 
        JOIN tb_user u ON p.id_masyarakat = u.id_user 
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $permohonan_terbaru = $stmt->fetchAll();

    // Statistik bulanan (6 bulan terakhir)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as bulan,
            COUNT(*) as total
        FROM tb_permohonan 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $stmt->execute();
    $monthly_stats = $stmt->fetchAll();

    // Hitung total keseluruhan
    $total_users = array_sum(array_column($user_stats, 'total'));
    $total_permohonan = array_sum(array_column($permohonan_stats, 'total'));
    $total_diajukan = 0;
    $total_diverifikasi = 0;

    foreach ($permohonan_stats as $stat) {
        if ($stat['status_permohonan'] == 'diajukan') {
            $total_diajukan = $stat['total'];
        } elseif ($stat['status_permohonan'] == 'diverifikasi') {
            $total_diverifikasi = $stat['total'];
        }
    }

} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard - Kantor Camat Sutera</title>
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
    <link rel="stylesheet" href="../../assets/css/demo.css" />

    <style>
        .card-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            transition: transform 0.3s ease;
        }

        .card-stats:hover {
            transform: translateY(-5px);
        }

        .card-stats.card-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .card-stats.card-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .card-stats.card-warning {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .card-stats.card-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .pulse-animation {
            animation: pulse 2s infinite;
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-diajukan {
            background: linear-gradient(135deg, #ffeaa7, #fab1a0);
            color: #2d3436;
        }

        .status-diverifikasi {
            background: linear-gradient(135deg, #81ecec, #74b9ff);
            color: #2d3436;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include '_component/sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="index.php" class="logo">
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
                            <h3 class="fw-bold mb-3">Dashboard Kantor Camat Sutera</h3>
                            <h6 class="op-7 mb-2">Sistem Informasi Pelayanan Masyarakat</h6>
                        </div>
                        <div class="ms-md-auto py-2 py-md-0">
                            <!-- Breadcrumb -->
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <i class="fa fa-home"></i> Dashboard
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round pulse-animation">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center text-white">
                                                <i class="fas fa-users fa-3x"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category text-white">Total Pengguna</p>
                                                <h4 class="card-title text-white"><?php echo $total_users; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-secondary card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center text-white">
                                                <i class="fas fa-file-alt fa-3x"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category text-white">Total Permohonan</p>
                                                <h4 class="card-title text-white"><?php echo $total_permohonan; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-success card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center text-white">
                                                <i class="fas fa-check-circle fa-3x"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category text-white">Diverifikasi</p>
                                                <h4 class="card-title text-white"><?php echo $total_diverifikasi; ?>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-warning card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center text-white">
                                                <i class="fas fa-clock fa-3x"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category text-white">Menunggu Verifikasi</p>
                                                <h4 class="card-title text-white"><?php echo $total_diajukan; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Statistik Permohonan Bulanan</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="min-height: 375px">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Kategori Permohonan</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <div class="card-title">Status Permohonan</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Applications -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-head-row card-tools-still-right">
                                        <h4 class="card-title">Permohonan Terbaru</h4>
                                        <div class="card-tools">
                                            <a href="permohonan_masuk.php" class="btn btn-label-info btn-round btn-sm">
                                                <span class="btn-label">
                                                    <i class="fa fa-eye"></i>
                                                </span>
                                                Lihat Semua
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th scope="col">Judul Permohonan</th>
                                                    <th scope="col">Pemohon</th>
                                                    <th scope="col">Kategori</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Tanggal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($permohonan_terbaru)): ?>
                                                    <?php foreach ($permohonan_terbaru as $permohonan): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($permohonan['judul_permohonan']); ?></strong>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($permohonan['nama_pemohon']); ?>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php echo htmlspecialchars($permohonan['kategori_permohonan']); ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <span
                                                                    class="status-badge status-<?php echo $permohonan['status_permohonan']; ?>">
                                                                    <?php echo ucfirst($permohonan['status_permohonan']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php echo $permohonan['created_at'] ? date('d/m/Y H:i', strtotime($permohonan['created_at'])) : '-'; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center py-4">
                                                            <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                                                            <br>
                                                            <span class="text-muted">Belum ada permohonan</span>
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
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    if (!empty($monthly_stats)) {
                        foreach ($monthly_stats as $stat) {
                            echo "'" . date('M Y', strtotime($stat['bulan'] . '-01')) . "',";
                        }
                    } else {
                        echo "'Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025', 'May 2025', 'Jun 2025'";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Permohonan',
                    data: [
                        <?php
                        if (!empty($monthly_stats)) {
                            foreach ($monthly_stats as $stat) {
                                echo $stat['total'] . ',';
                            }
                        } else {
                            echo '0, 0, 0, 0, 0, 0';
                        }
                        ?>
                    ],
                    borderColor: '#177dff',
                    backgroundColor: 'rgba(23, 125, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php
                    if (!empty($kategori_stats)) {
                        foreach ($kategori_stats as $stat) {
                            echo "'" . $stat['kategori_permohonan'] . "',";
                        }
                    } else {
                        echo "'Belum ada data'";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php
                        if (!empty($kategori_stats)) {
                            foreach ($kategori_stats as $stat) {
                                echo $stat['total'] . ',';
                            }
                        } else {
                            echo '0';
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#177dff',
                        '#f3545d',
                        '#fdaf4b',
                        '#1d7af3',
                        '#f25961'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php
                    if (!empty($permohonan_stats)) {
                        foreach ($permohonan_stats as $stat) {
                            echo "'" . ucfirst($stat['status_permohonan']) . "',";
                        }
                    } else {
                        echo "'Belum ada data'";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php
                        if (!empty($permohonan_stats)) {
                            foreach ($permohonan_stats as $stat) {
                                echo $stat['total'] . ',';
                            }
                        } else {
                            echo '0';
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#fdaf4b',
                        '#1d7af3'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>