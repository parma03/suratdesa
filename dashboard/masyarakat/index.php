<?php
session_start();
include '../../database/koneksi.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Pegawai') {
        header("Location: dashboard/pegawai/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Administrator') {
        header("Location: dashboard/administrator/index.php");
        exit();
    }
} else {
    header("Location: ../../index.php");
    exit();
}

// Get kategori from enum
function getKategoriEnum($pdo)
{
    $query = "SHOW COLUMNS FROM tb_permohonan LIKE 'kategori_permohonan'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $enumString = $row['Type'];
        preg_match('/enum\((.*)\)/', $enumString, $matches);
        if (isset($matches[1])) {
            $enumValues = str_getcsv($matches[1], ',', "'");
            return $enumValues;
        }
    }
    return [];
}

$kategoriOptions = getKategoriEnum($pdo);

// Handle Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        switch ($_POST['action']) {
            case 'create':
                $id_masyarakat = $_SESSION['id_user'];
                $judul_permohonan = $_POST['judul_permohonan'];
                $deskripsi_permohonan = $_POST['deskripsi_permohonan'];
                $kategori_permohonan = $_POST['kategori_permohonan'];
                $status_permohonan = 'diajukan';

                // Validate kategori
                if (!in_array($kategori_permohonan, $kategoriOptions)) {
                    echo json_encode(['status' => 'error', 'message' => 'Kategori permohonan tidak valid!']);
                    exit;
                }

                // Start transaction
                $pdo->beginTransaction();

                // Insert permohonan
                $stmt = $pdo->prepare("INSERT INTO tb_permohonan (id_masyarakat, judul_permohonan, `deskripsi permohonan`, kategori_permohonan, status_permohonan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$id_masyarakat, $judul_permohonan, $deskripsi_permohonan, $kategori_permohonan, $status_permohonan]);

                if (!$result) {
                    $pdo->rollback();
                    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan permohonan.']);
                    exit;
                }

                $id_permohonan = $pdo->lastInsertId();

                // Handle multiple file uploads
                if (isset($_FILES['file_permohonan']) && !empty($_FILES['file_permohonan']['name'][0])) {
                    $uploadDir = '../../assets/files/';

                    // Create directory if it doesn't exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    $maxFileSize = 5 * 1024 * 1024; // 5MB

                    $fileCount = count($_FILES['file_permohonan']['name']);
                    $uploadedFiles = [];

                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($_FILES['file_permohonan']['error'][$i] === UPLOAD_ERR_OK) {
                            $fileName = $_FILES['file_permohonan']['name'][$i];
                            $fileTmpName = $_FILES['file_permohonan']['tmp_name'][$i];
                            $fileSize = $_FILES['file_permohonan']['size'][$i];
                            $fileMimeType = mime_content_type($fileTmpName);

                            // Check file type
                            if (!in_array($fileMimeType, $allowedTypes)) {
                                $pdo->rollback();
                                echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung! Gunakan PDF, JPG, PNG, GIF, DOC, atau DOCX.']);
                                exit;
                            }

                            // Check file size
                            if ($fileSize > $maxFileSize) {
                                $pdo->rollback();
                                echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar! Maksimal 5MB per file.']);
                                exit;
                            }

                            // Generate unique filename
                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            $uniqueFileName = time() . '_' . $i . '_' . uniqid() . '.' . $fileExtension;
                            $uploadPath = $uploadDir . $uniqueFileName;

                            // Move uploaded file
                            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                                $uploadedFiles[] = $uniqueFileName;
                            } else {
                                $pdo->rollback();
                                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload file: ' . $fileName]);
                                exit;
                            }
                        }
                    }

                    // Insert file records
                    $fileStmt = $pdo->prepare("INSERT INTO tb_file_permohonan (id_permohonan, file_permohonan) VALUES (?, ?)");
                    foreach ($uploadedFiles as $uploadedFile) {
                        $fileResult = $fileStmt->execute([$id_permohonan, $uploadedFile]);
                        if (!$fileResult) {
                            $pdo->rollback();
                            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data file.']);
                            exit;
                        }
                    }
                }

                // Commit transaction
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Permohonan berhasil diajukan!']);
                break;
        }
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kantor Camat Sutera - Dashboard Masyarakat</title>
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

        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: #007bff;
            background: #e7f3ff;
        }

        .file-upload-area.dragover {
            border-color: #007bff;
            background: #e7f3ff;
        }

        .file-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            margin: 4px 0;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .file-item .file-name {
            flex: 1;
            margin-right: 10px;
            font-size: 14px;
        }

        .file-item .file-size {
            color: #6c757d;
            font-size: 12px;
            margin-right: 10px;
        }

        .file-item .remove-file {
            color: #dc3545;
            cursor: pointer;
            font-size: 16px;
        }

        .file-item .remove-file:hover {
            color: #a71d2a;
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
                            <h3 class="fw-bold mb-3">Buat Permohonan</h3>
                            <h6 class="op-7 mb-2">Ajukan permohonan baru ke kantor camat</h6>
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
                                        Buat Permohonan
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Form Permohonan</div>
                                </div>
                                <div class="card-body">
                                    <form id="formPermohonan" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="create">

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="judul_permohonan">Judul Permohonan <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="judul_permohonan"
                                                        name="judul_permohonan" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="kategori_permohonan">Kategori Permohonan <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-control" id="kategori_permohonan"
                                                        name="kategori_permohonan" required>
                                                        <option value="">Pilih Kategori</option>
                                                        <?php foreach ($kategoriOptions as $kategori): ?>
                                                            <option value="<?php echo htmlspecialchars($kategori); ?>">
                                                                <?php echo htmlspecialchars($kategori); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="deskripsi_permohonan">Deskripsi Permohonan <span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control" id="deskripsi_permohonan"
                                                name="deskripsi_permohonan" rows="5" required
                                                placeholder="Jelaskan detail permohonan Anda..."></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label>File Pendukung</label>
                                            <div class="file-upload-area" id="fileUploadArea">
                                                <i class="fa fa-upload fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-2">Drag & drop file atau klik untuk memilih</p>
                                                <p class="text-muted small mb-0">Format: PDF, JPG, PNG, GIF, DOC, DOCX
                                                    (Max: 5MB per file)</p>
                                                <input type="file" id="file_permohonan" name="file_permohonan[]"
                                                    multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx"
                                                    style="display: none;">
                                            </div>
                                            <div class="file-list mt-3" id="fileList"></div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-paper-plane"></i> Ajukan Permohonan
                                            </button>
                                            <button type="reset" class="btn btn-secondary">
                                                <i class="fa fa-times"></i> Reset
                                            </button>
                                        </div>
                                    </form>
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
        let selectedFiles = [];

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

        // Format file size
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Validate file
        function validateFile(file) {
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                showNotification('error', 'Format file tidak didukung! Gunakan PDF, JPG, PNG, GIF, DOC, atau DOCX.');
                return false;
            }

            if (file.size > maxSize) {
                showNotification('error', 'Ukuran file terlalu besar! Maksimal 5MB per file.');
                return false;
            }

            return true;
        }

        // Update file list display
        function updateFileList() {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${formatFileSize(file.size)}</div>
                    <div class="remove-file" onclick="removeFile(${index})">
                        <i class="fa fa-times"></i>
                    </div>
                `;
                fileList.appendChild(fileItem);
            });
        }

        // Remove file
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
        }

        // File upload area click
        document.getElementById('fileUploadArea').addEventListener('click', function () {
            document.getElementById('file_permohonan').click();
        });

        // File input change
        document.getElementById('file_permohonan').addEventListener('change', function (e) {
            const files = Array.from(e.target.files);

            files.forEach(file => {
                if (validateFile(file)) {
                    selectedFiles.push(file);
                }
            });

            updateFileList();
            e.target.value = ''; // Reset input
        });

        // Drag and drop functionality
        const fileUploadArea = document.getElementById('fileUploadArea');

        fileUploadArea.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = Array.from(e.dataTransfer.files);

            files.forEach(file => {
                if (validateFile(file)) {
                    selectedFiles.push(file);
                }
            });

            updateFileList();
        });

        // Form submission
        document.getElementById('formPermohonan').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('judul_permohonan', document.getElementById('judul_permohonan').value);
            formData.append('kategori_permohonan', document.getElementById('kategori_permohonan').value);
            formData.append('deskripsi_permohonan', document.getElementById('deskripsi_permohonan').value);

            // Add selected files
            selectedFiles.forEach(file => {
                formData.append('file_permohonan[]', file);
            });

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Mengirim...';
            submitBtn.disabled = true;

            fetch('', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showNotification('success', data.message);
                        this.reset();
                        selectedFiles = [];
                        updateFileList();
                    } else {
                        showNotification('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Terjadi kesalahan pada server.');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Reset form
        document.getElementById('formPermohonan').addEventListener('reset', function () {
            selectedFiles = [];
            updateFileList();
        });

        // Initialize tooltips
        $(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>

</html>