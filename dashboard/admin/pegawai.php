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

// Handle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'create':
                $nama = $_POST['nama'];
                $email = $_POST['email'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = 'Pegawai';
                $photo_profile = null;

                // Check if email already exists first
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_user WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetchColumn() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar!']);
                    exit;
                }

                // Handle file upload
                if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../assets/img/avatars/';

                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileExtension = strtolower(pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION));
                    $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;

                    // Check file type
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileMimeType = mime_content_type($_FILES['photo_profile']['tmp_name']);

                    if (!in_array($fileMimeType, $allowedTypes)) {
                        echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.']);
                        exit;
                    }

                    // Check file size (max 2MB)
                    if ($_FILES['photo_profile']['size'] > 2 * 1024 * 1024) {
                        echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar! Maksimal 2MB.']);
                        exit;
                    }

                    if (move_uploaded_file($_FILES['photo_profile']['tmp_name'], $uploadPath)) {
                        $photo_profile = $fileName;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload foto profil.']);
                        exit;
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO tb_user (nama, email, password, role, photo_profile, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$nama, $email, $password, $role, $photo_profile]);

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Pegawai berhasil ditambahkan!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan pegawai.']);
                }
                break;

            case 'update':
                $id = $_POST['id_user'];
                $nama = $_POST['nama'];
                $email = $_POST['email'];
                $photo_profile = $_POST['old_photo'] ?? null;

                // Check if email already exists for other users
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_user WHERE email = ? AND id_user != ?");
                $checkStmt->execute([$email, $id]);
                if ($checkStmt->fetchColumn() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Email sudah digunakan oleh user lain!']);
                    exit;
                }

                // Handle file upload
                if (isset($_FILES['photo_profile']) && $_FILES['photo_profile']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../../assets/img/avatars/';

                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileExtension = strtolower(pathinfo($_FILES['photo_profile']['name'], PATHINFO_EXTENSION));
                    $fileName = time() . '_' . uniqid() . '.' . $fileExtension;
                    $uploadPath = $uploadDir . $fileName;

                    // Check file type
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $fileMimeType = mime_content_type($_FILES['photo_profile']['tmp_name']);

                    if (!in_array($fileMimeType, $allowedTypes)) {
                        echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.']);
                        exit;
                    }

                    // Check file size (max 2MB)
                    if ($_FILES['photo_profile']['size'] > 2 * 1024 * 1024) {
                        echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar! Maksimal 2MB.']);
                        exit;
                    }

                    if (move_uploaded_file($_FILES['photo_profile']['tmp_name'], $uploadPath)) {
                        // Delete old photo if exists
                        if ($photo_profile && file_exists($uploadDir . $photo_profile)) {
                            unlink($uploadDir . $photo_profile);
                        }
                        $photo_profile = $fileName;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload foto profil.']);
                        exit;
                    }
                }

                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE tb_user SET nama = ?, email = ?, password = ?, photo_profile = ?, updated_at = NOW() WHERE id_user = ?");
                    $result = $stmt->execute([$nama, $email, $password, $photo_profile, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE tb_user SET nama = ?, email = ?, photo_profile = ?, updated_at = NOW() WHERE id_user = ?");
                    $result = $stmt->execute([$nama, $email, $photo_profile, $id]);
                }

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Pegawai berhasil diupdate!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate pegawai.']);
                }
                break;

            case 'delete':
                $id = $_POST['id_user'];

                // Get photo to delete
                $stmt = $pdo->prepare("SELECT photo_profile FROM tb_user WHERE id_user = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && $user['photo_profile']) {
                    $photoPath = '../../assets/img/avatars/' . $user['photo_profile'];
                    if (file_exists($photoPath)) {
                        unlink($photoPath);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM tb_user WHERE id_user = ?");
                $result = $stmt->execute([$id]);

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Pegawai berhasil dihapus!']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus pegawai.']);
                }
                break;

            case 'get_detail':
                $id = $_POST['id_user'];
                $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
                $stmt->execute([$id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    echo json_encode(['status' => 'success', 'data' => $user]);
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

// Fetch all data
$stmt = $pdo->prepare("SELECT * FROM tb_user WHERE role = 'Pegawai' ORDER BY created_at DESC");
$stmt->execute();
$data = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kantor Camat Sutera - Data Pegawai</title>
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
        .avatar-preview {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .avatar-preview-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
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
                            <h3 class="fw-bold mb-3">Pegawai</h3>
                            <h6 class="op-7 mb-2">Kelola Data Pegawai</h6>
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
                                        Pegawai
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
                                        <h4 class="card-title">Data Pegawai</h4>
                                        <button class="btn btn-primary btn-round ms-auto" onclick="showCreateForm()">
                                            <i class="fa fa-plus"></i>
                                            Tambah Pegawai
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="pegawaiTable" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Foto</th>
                                                    <th>Nama</th>
                                                    <th>Email</th>
                                                    <th>Role</th>
                                                    <th>Dibuat</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1;
                                                foreach ($data as $row): ?>
                                                    <tr>
                                                        <td><?= $no++ ?></td>
                                                        <td>
                                                            <?php if ($row['photo_profile']): ?>
                                                                <img src="../../assets/img/avatars/<?= $row['photo_profile'] ?>"
                                                                    alt="Avatar" class="avatar-preview">
                                                            <?php else: ?>
                                                                <div
                                                                    class="avatar-preview bg-secondary d-flex align-items-center justify-content-center text-white">
                                                                    <i class="fa fa-user"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                                        <td><span class="badge badge-success"><?= $row['role'] ?></span>
                                                        </td>
                                                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                                        <td>
                                                            <div class="form-button-action">
                                                                <button type="button" class="btn btn-link btn-info btn-lg"
                                                                    onclick="viewDetail(<?= $row['id_user'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Lihat Detail">
                                                                    <i class="fa fa-eye"></i>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-link btn-primary btn-lg"
                                                                    onclick="showEditForm(<?= $row['id_user'] ?>)"
                                                                    data-bs-toggle="tooltip" title="Edit">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-link btn-danger btn-lg"
                                                                    onclick="deleteData(<?= $row['id_user'] ?>)"
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
        $(document).ready(function () {
            // Initialize DataTable
            $('#pegawaiTable').DataTable({
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

        // Show Create Form
        function showCreateForm() {
            swal({
                title: 'Tambah Pegawai',
                content: {
                    element: "div",
                    attributes: {
                        innerHTML: additionalCSS + `
                    <form id="createForm" enctype="multipart/form-data">
                        <div class="profile-preview-container">
                            <div id="createPreview" class="avatar-placeholder">
                                <i class="fa fa-user fa-2x"></i>
                            </div>
                            <div class="file-input-wrapper">
                                <label class="form-label">Foto Profil</label>
                                <input type="file" class="form-control" name="photo_profile" accept="image/*" onchange="previewImage(this, 'createPreview')">
                                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </form>
                `
                    }
                },
                buttons: {
                    cancel: {
                        visible: true,
                        text: 'Batal',
                        className: 'btn btn-secondary'
                    },
                    confirm: {
                        text: 'Simpan',
                        className: 'btn btn-primary',
                        closeModal: false
                    }
                }
            }).then((result) => {
                if (result) {
                    submitForm('create');
                }
            });
        }

        // Show Edit Form
        function showEditForm(id) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'get_detail',
                    id_user: id
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        const user = response.data;
                        const photoPreview = user.photo_profile ?
                            `<img src="../../assets/img/avatars/${user.photo_profile}" class="avatar-preview-large" id="editPreview">` :
                            '<div class="avatar-placeholder" id="editPreview"><i class="fa fa-user fa-2x"></i></div>';

                        swal({
                            title: 'Edit Pegawai',
                            content: {
                                element: "div",
                                attributes: {
                                    innerHTML: additionalCSS + `
                                <form id="editForm" enctype="multipart/form-data">
                                    <input type="hidden" name="id_user" value="${user.id_user}">
                                    <input type="hidden" name="old_photo" value="${user.photo_profile || ''}">
                                    <div class="profile-preview-container">
                                        ${photoPreview}
                                        <div class="file-input-wrapper">
                                            <label class="form-label">Foto Profil</label>
                                            <input type="file" class="form-control" name="photo_profile" accept="image/*" onchange="previewImage(this, 'editPreview')">
                                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input type="text" class="form-control" name="nama" value="${user.nama}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" class="form-control" name="email" value="${user.email}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Password (Kosongkan jika tidak ingin mengubah)</label>
                                        <input type="password" class="form-control" name="password">
                                    </div>
                                </form>
                            `
                                }
                            },
                            buttons: {
                                cancel: {
                                    visible: true,
                                    text: 'Batal',
                                    className: 'btn btn-secondary'
                                },
                                confirm: {
                                    text: 'Update',
                                    className: 'btn btn-primary',
                                    closeModal: false
                                }
                            }
                        }).then((result) => {
                            if (result) {
                                submitForm('update');
                            }
                        });
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function () {
                    showNotification('error', 'Gagal mengambil data user');
                }
            });
        }

        // View Detail
        function viewDetail(id) {
            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'get_detail',
                    id_user: id
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        const user = response.data;
                        const photoPreview = user.photo_profile ?
                            `<img src="../../assets/img/avatars/${user.photo_profile}" class="avatar-preview-large">` :
                            '<div class="avatar-placeholder"><i class="fa fa-user fa-2x"></i></div>';

                        swal({
                            title: 'Detail Pegawai',
                            content: {
                                element: "div",
                                attributes: {
                                    innerHTML: additionalCSS + `
                                <div class="profile-preview-container">
                                    ${photoPreview}
                                </div>
                                <table class="table table-bordered">
                                    <tr>
                                        <td><strong>Nama</strong></td>
                                        <td>${user.nama}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email</strong></td>
                                        <td>${user.email}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Role</strong></td>
                                        <td><span class="badge badge-success">${user.role}</span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dibuat</strong></td>
                                        <td>${new Date(user.created_at).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</td>
                                    </tr>
                                    ${user.updated_at ? `
                                    <tr>
                                        <td><strong>Diupdate</strong></td>
                                        <td>${new Date(user.updated_at).toLocaleDateString('id-ID', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}</td>
                                    </tr>
                                    ` : ''}
                                </table>
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
                title: 'Hapus Pegawai',
                text: 'Apakah Anda yakin ingin menghapus data ini?',
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
                            id_user: id
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
                            showNotification('error', 'Gagal menghapus data');
                        }
                    });
                }
            });
        }

        function previewImage(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);

            if (file) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    // Ganti dengan image element
                    preview.innerHTML = `<img src="${e.target.result}" class="avatar-preview-large">`;
                };

                reader.readAsDataURL(file);
            }
        }

        // Submit Form
        function submitForm(action) {
            const formId = action === 'create' ? 'createForm' : 'editForm';
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            formData.append('action', action);

            // Show loading
            swal({
                title: 'Memproses...',
                text: 'Mohon tunggu sebentar',
                icon: 'info',
                buttons: false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });

            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    swal.close(); // Tutup loading dialog

                    if (response.status === 'success') {
                        showNotification('success', response.message);

                        // Reload data table tanpa refresh halaman
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('error', response.message);
                    }
                },
                error: function (xhr, status, error) {
                    swal.close(); // Tutup loading dialog
                    console.error('Ajax Error:', xhr.responseText);
                    showNotification('error', 'Terjadi kesalahan saat memproses data. Silakan coba lagi.');
                }
            });
        }

        // Show Notification Function
        function showNotification(type, message) {
            const bgColor = type === 'success' ? 'success' : 'danger';
            const icon = type === 'success' ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle';
            const title = type === 'success' ? 'Berhasil!' : 'Error!';

            $.notify({
                icon: icon,
                title: title,
                message: message,
            }, {
                type: bgColor,
                placement: {
                    from: "top",
                    align: "right"
                },
                time: 4000,
                delay: 0,
                z_index: 9999,
                animate: {
                    enter: 'animated fadeInDown',
                    exit: 'animated fadeOutUp'
                },
                template: `
                    <div data-notify="container" class="col-11 col-md-4 alert alert-{0} notification-${type}" role="alert">
                        <button type="button" aria-hidden="true" class="btn-close" data-notify="dismiss"></button>
                        <div class="notification-content">
                            <span data-notify="icon" class="notification-icon"></span>
                            <div class="notification-text">
                                <span data-notify="title" class="notification-title">{1}</span>
                                <span data-notify="message" class="notification-message">{2}</span>
                            </div>
                        </div>
                    </div>
                `
            });
        }

        // Additional CSS untuk styling
        const additionalCSS = `
            <style>
                .profile-preview-container {
                    text-align: center;
                    margin-bottom: 20px;
                }
                
                .avatar-preview-large {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    object-fit: cover;
                    margin-bottom: 15px;
                    border: 3px solid #e9ecef;
                }
                
                .avatar-placeholder {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    background-color: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 15px;
                    border: 3px solid #e9ecef;
                    color: #6c757d;
                }
                
                .file-input-wrapper {
                    max-width: 300px;
                    margin: 0 auto;
                }
                
                .form-group {
                    margin-bottom: 15px;
                    text-align: left;
                }
                
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    color: #495057;
                }
                
                .form-control {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ced4da;
                    border-radius: 6px;
                    font-size: 14px;
                }
                
                .form-control:focus {
                    border-color: #80bdff;
                    outline: 0;
                    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
                }
                
                .table {
                    margin-top: 20px;
                }
                
                .table td {
                    padding: 8px 12px;
                    vertical-align: middle;
                }
                
                .badge {
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                }
                
                .badge-success {
                    background-color: #28a745;
                    color: white;
                }
                
                .text-muted {
                    color: #6c757d !important;
                    font-size: 12px;
                }
                
                /* Custom notification animations */
                @keyframes fadeInRight {
                    from {
                        opacity: 0;
                        transform: translate3d(100%, 0, 0);
                    }
                    to {
                        opacity: 1;
                        transform: translate3d(0, 0, 0);
                    }
                }
                
                @keyframes fadeOutRight {
                    from {
                        opacity: 1;
                    }
                    to {
                        opacity: 0;
                        transform: translate3d(100%, 0, 0);
                    }
                }
                
                .animated {
                    animation-duration: 0.3s;
                    animation-fill-mode: both;
                }
                
                .fadeInRight {
                    animation-name: fadeInRight;
                }
                
                .fadeOutRight {
                    animation-name: fadeOutRight;
                }
                
                /* Success notification styling */
                .notification-success {
                    background: linear-gradient(135deg, #28a745, #20c997) !important;
                    color: white !important;
                    border: none !important;
                    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3) !important;
                }
                
                /* Error notification styling */
                .notification-error {
                    background: linear-gradient(135deg, #dc3545, #e74c3c) !important;
                    color: white !important;
                    border: none !important;
                    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3) !important;
                }
                
                .notification-success .btn-close,
                .notification-error .btn-close {
                    color: white !important;
                    opacity: 0.8;
                }
                
                .notification-success .btn-close:hover,
                .notification-error .btn-close:hover {
                    opacity: 1;
                }
            </style>
        `;

        // Initialize tooltips
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });

        // Handle form submission dengan enter key
        $(document).on('keypress', 'form input', function (e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                const form = $(this).closest('form');
                const formId = form.attr('id');

                if (formId === 'createForm') {
                    submitForm('create');
                } else if (formId === 'editForm') {
                    submitForm('update');
                }
            }
        });

        // Validate file before upload
        function validateFile(input) {
            const file = input.files[0];
            if (!file) return true;

            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const fileType = file.type.toLowerCase();

            if (!allowedTypes.includes(fileType)) {
                showNotification('error', 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.');
                input.value = '';
                return false;
            }

            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('error', 'Ukuran file terlalu besar! Maksimal 2MB.');
                input.value = '';
                return false;
            }

            return true;
        }

        // Add file validation to file inputs
        $(document).on('change', 'input[type="file"][name="photo_profile"]', function () {
            validateFile(this);
        });
    </script>
</body>

</html>