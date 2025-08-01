<?php
session_start();
include 'database/koneksi.php';

// Inisialisasi variabel untuk alert
$alert_message = '';
$alert_type = '';
$alert_title = '';
$alert_icon = '';

// Pengecekan session untuk redirect jika sudah login
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Administrator') {
        header("Location: dashboard/admin/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Pegawai') {
        header("Location: dashboard/pegwawai/index.php");
        exit();
    } else if ($_SESSION['role'] === 'Masyarakat') {
        header("Location: dashboard/masyarakat/index.php");
        exit();
    }
}

// Ambil alert dari session jika ada
if (isset($_SESSION['alert_type'])) {
    $alert_type = $_SESSION['alert_type'];
    $alert_title = $_SESSION['alert_title'];
    $alert_message = $_SESSION['alert_message'];
    $alert_icon = $_SESSION['alert_icon'] ?? '';

    // Hapus session alert setelah ditampilkan
    unset($_SESSION['alert_type'], $_SESSION['alert_title'], $_SESSION['alert_message'], $_SESSION['alert_icon']);
}

// Proses Register
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $nonik = trim($_POST['nonik']);
    $nokk = trim($_POST['nokk']);
    $alamat = trim($_POST['alamat']);
    $kecamatan = trim($_POST['kecamatan']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validasi input
    if (empty($nama) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Peringatan';
        $_SESSION['alert_message'] = 'Semua field harus diisi!';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else if ($password !== $confirm_password) {
        $_SESSION['alert_type'] = 'danger';
        $_SESSION['alert_title'] = 'Error';
        $_SESSION['alert_message'] = 'Password dan konfirmasi password tidak cocok!';
        $_SESSION['alert_icon'] = 'fas fa-times-circle';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else if (strlen($password) < 6) {
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Peringatan';
        $_SESSION['alert_message'] = 'Password minimal 6 karakter!';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        try {
            // Cek apakah email sudah ada
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tb_user WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['alert_type'] = 'warning';
                $_SESSION['alert_title'] = 'Peringatan';
                $_SESSION['alert_message'] = 'Email sudah terdaftar!';
                $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }

            // Hash password sebelum menyimpan
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Validasi';

            // Insert user baru dengan password yang sudah di-hash
            $stmt = $pdo->prepare("INSERT INTO tb_user (nama, nokk, nonik, alamat, kecamatan, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([$nama, $nokk, $nonik, $alamat, $kecamatan, $email, $hashedPassword, $role]);

            if ($result) {
                $_SESSION['alert_type'] = 'success';
                $_SESSION['alert_title'] = 'Berhasil';
                $_SESSION['alert_message'] = 'Registrasi berhasil! Silakan login.';
                $_SESSION['alert_icon'] = 'fas fa-check-circle';
            } else {
                $_SESSION['alert_type'] = 'danger';
                $_SESSION['alert_title'] = 'Error';
                $_SESSION['alert_message'] = 'Gagal melakukan registrasi. Silakan coba lagi.';
                $_SESSION['alert_icon'] = 'fas fa-times-circle';
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $_SESSION['alert_type'] = 'danger';
            $_SESSION['alert_title'] = 'Error Database';
            $_SESSION['alert_message'] = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
            $_SESSION['alert_icon'] = 'fas fa-times-circle';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validasi input
    if (empty($email) || empty($password)) {
        $_SESSION['alert_type'] = 'warning';
        $_SESSION['alert_title'] = 'Peringatan';
        $_SESSION['alert_message'] = 'Email dan password harus diisi!';
        $_SESSION['alert_icon'] = 'fas fa-exclamation-triangle';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        try {
            // Query untuk mencari user berdasarkan email saja
            $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifikasi password menggunakan password_verify
            if ($user && password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['photo_profile'] = $user['photo_profile'] ?? '';

                $_SESSION['alert_type'] = 'success';
                $_SESSION['alert_title'] = 'Berhasil';
                $_SESSION['alert_message'] = 'Login berhasil! Anda akan diarahkan ke dashboard.';
                $_SESSION['alert_icon'] = 'fas fa-check-circle';

                // Redirect berdasarkan role
                if ($user['role'] === 'Administrator') {
                    header("Location: dashboard/admin/index.php");
                } else if ($user['role'] === 'Masyarakat') {
                    header("Location: dashboard/masyarakat/index.php");
                } else if ($user['role'] === 'Pegawai') {
                    header("Location: dashboard/pegawai/index.php");
                }
                exit();
            } else {
                // Login gagal
                $_SESSION['alert_type'] = 'danger';
                $_SESSION['alert_title'] = 'Error';
                $_SESSION['alert_message'] = 'Email atau password salah!';
                $_SESSION['alert_icon'] = 'fas fa-times-circle';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } catch (PDOException $e) {
            // Error database
            $_SESSION['alert_type'] = 'danger';
            $_SESSION['alert_title'] = 'Error Database';
            $_SESSION['alert_message'] = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
            $_SESSION['alert_icon'] = 'fas fa-times-circle';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kantor Camat Sutera - Login</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
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
                urls: ["assets/css/fonts.min.css"],
            },
            active: function() {
                sessionStorage.fonts = true;
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link rel="stylesheet" href="assets/css/demo.css" />

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-pills .nav-link.active {
            background-color: #1572E8;
        }

        .btn-primary {
            background-color: #1572E8;
            border-color: #1572E8;
        }

        .btn-primary:hover {
            background-color: #0f5bb8;
            border-color: #0f5bb8;
        }

        .form-control:focus {
            border-color: #1572E8;
            box-shadow: 0 0 0 0.2rem rgba(21, 114, 232, 0.25);
        }

        /* Notification Styles */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .notification {
            min-width: 300px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification .alert {
            margin-bottom: 0;
            border: none;
        }

        .notification .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .notification .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .notification .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .notification .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
    </style>
</head>

<body>
    <!-- Notification Container -->
    <div class="notification-container" id="notificationContainer"></div>

    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h3 class="fw-bold text-primary">Kantor Camat Sutera</h3>
                                <p class="text-muted">Silakan login atau daftar untuk melanjutkan</p>
                            </div>

                            <!-- Nav Pills untuk Login/Register -->
                            <ul class="nav nav-pills nav-justified mb-4" id="authTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="login-tab" data-bs-toggle="pill"
                                        data-bs-target="#login" type="button" role="tab">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="register-tab" data-bs-toggle="pill"
                                        data-bs-target="#register" type="button" role="tab">
                                        <i class="fas fa-user-plus me-2"></i>Register
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="authTabsContent">
                                <!-- Login Form -->
                                <div class="tab-pane fade show active" id="login" role="tabpanel">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="loginEmail" class="form-label">Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" class="form-control" id="loginEmail" name="email"
                                                    placeholder="Masukkan email" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="loginPassword" class="form-label">Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="loginPassword"
                                                    name="password" placeholder="Masukkan password" required>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('loginPassword', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" name="login" class="btn btn-primary btn-lg">
                                                <i class="fas fa-sign-in-alt me-2"></i>Login
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Register Form -->
                                <div class="tab-pane fade" id="register" role="tabpanel">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="registerNama" class="form-label">Nama Lengkap</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <input type="text" class="form-control" id="registerNama" name="nama"
                                                    placeholder="Masukkan nama lengkap" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registerNokk" class="form-label">No. KK</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                                <input type="number" class="form-control" id="registerNokk" name="nokk"
                                                    placeholder="Masukkan NO. KK" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registerNonik" class="form-label">No. NIK</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-users"></i></span>
                                                <input type="number" class="form-control" id="registerNonik" name="nonik"
                                                    placeholder="Masukkan NO. NIK" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registerAlamat" class="form-label">Alamat</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-map"></i></span>
                                                <input type="text" class="form-control" id="registerAlamat" name="alamat"
                                                    placeholder="Masukkan Alamat lengkap" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registerKecamatan" class="form-label">Kecamatan</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-thumbtack"></i></span>
                                                <input type="text" class="form-control" id="registerKecamatan" name="kecamatan"
                                                    placeholder="Masukkan Kecamatan" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registerEmail" class="form-label">Email</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                <input type="email" class="form-control" id="registerEmail" name="email"
                                                    placeholder="Masukkan email" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registerPassword" class="form-label">Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="registerPassword"
                                                    name="password" placeholder="Masukkan password (min. 6 karakter)"
                                                    required>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('registerPassword', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="confirmPassword" class="form-label">Konfirmasi Password</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                <input type="password" class="form-control" id="confirmPassword"
                                                    name="confirm_password" placeholder="Konfirmasi password" required>
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePassword('confirmPassword', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" name="register" class="btn btn-success btn-lg">
                                                <i class="fas fa-user-plus me-2"></i>Daftar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/plugin/chart.js/chart.min.js"></script>
    <script src="assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
    <script src="assets/js/plugin/chart-circle/circles.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/js/plugin/jsvectormap/world.js"></script>
    <script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <script>
        // Function to toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Function to show notification
        function showNotification(type, title, message, icon = '') {
            const container = document.getElementById('notificationContainer');
            const notificationId = 'notification-' + Date.now();

            let iconClass = '';
            let alertClass = '';

            switch (type) {
                case 'success':
                    alertClass = 'alert-success';
                    iconClass = icon || 'fas fa-check-circle';
                    break;
                case 'danger':
                    alertClass = 'alert-danger';
                    iconClass = icon || 'fas fa-times-circle';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    iconClass = icon || 'fas fa-exclamation-triangle';
                    break;
                case 'info':
                    alertClass = 'alert-info';
                    iconClass = icon || 'fas fa-info-circle';
                    break;
                default:
                    alertClass = 'alert-primary';
                    iconClass = icon || 'fas fa-bell';
            }

            const notificationHTML = `
                <div class="notification" id="${notificationId}">
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                        <i class="${iconClass} me-2"></i>
                        <strong>${title}</strong> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" onclick="removeNotification('${notificationId}')"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', notificationHTML);

            // Auto remove after 5 seconds
            setTimeout(() => {
                removeNotification(notificationId);
            }, 5000);
        }

        // Function to remove notification
        function removeNotification(notificationId) {
            const notification = document.getElementById(notificationId);
            if (notification) {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }

        // Show notification if there's an alert from PHP
        <?php if (!empty($alert_type) && !empty($alert_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('<?php echo $alert_type; ?>', '<?php echo $alert_title; ?>', '<?php echo $alert_message; ?>', '<?php echo $alert_icon; ?>');
            });
        <?php endif; ?>

        // Form validation
        document.getElementById('register').addEventListener('submit', function(e) {
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('danger', 'Error', 'Password dan konfirmasi password tidak cocok!', 'fas fa-times-circle');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                showNotification('warning', 'Peringatan', 'Password minimal 6 karakter!', 'fas fa-exclamation-triangle');
                return false;
            }
        });

        // Real-time password confirmation check
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = this.value;

            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    </script>
</body>

</html>