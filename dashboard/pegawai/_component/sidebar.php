<?php
// Ambil nama file saat ini
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
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
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
                    <a href="index.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Data Permohonan</h4>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#permohonan">
                        <i class="fas fa-envelope"></i>
                        <p>Kelola Permohonan</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?= in_array($current_page, ['permohonan_masuk.php', 'permohonan_selesai.php']) ? 'show' : '' ?>"
                        id="permohonan">
                        <ul class="nav nav-collapse">
                            <li class="<?= $current_page == 'permohonan_masuk.php' ? 'active' : '' ?>">
                                <a href="permohonan_masuk.php">
                                    <span class="sub-item">Permohonan Masuk</span>
                                </a>
                            </li>
                            <li class="<?= $current_page == 'permohonan_selesai.php' ? 'active' : '' ?>">
                                <a href="permohonan_selesai.php">
                                    <span class="sub-item">Permohonan Selesai</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-section">
                    <span class="sidebar-mini-icon">
                        <i class="fa fa-ellipsis-h"></i>
                    </span>
                    <h4 class="text-section">Arsip Laporan</h4>
                </li>
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#laporan">
                        <i class="fas fa-envelope"></i>
                        <p>Laporan Permohonan</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?= in_array($current_page, ['laporan_masuk.php', 'laporan_selesai.php']) ? 'show' : '' ?>"
                        id="laporan">
                        <ul class="nav nav-collapse">
                            <li class="<?= $current_page == 'laporan_masuk.php' ? 'active' : '' ?>">
                                <a href="laporan_masuk.php">
                                    <span class="sub-item">Laporan Masuk</span>
                                </a>
                            </li>
                            <li class="<?= $current_page == 'laporan_selesai.php' ? 'active' : '' ?>">
                                <a href="laporan_selesai.php">
                                    <span class="sub-item">Laporan Selesai</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->