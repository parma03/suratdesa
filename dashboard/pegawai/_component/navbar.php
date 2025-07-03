<?php
// Ambil data user untuk form (hanya jika diperlukan untuk tampilan)
if (isset($_SESSION['id_user'])) {
    $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
    $stmt->execute([$_SESSION['id_user']]);
    $user_data = $stmt->fetch();
}
?>

<!-- Navbar Header -->
<nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
    <div class="container-fluid">
        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
            <li class="nav-item topbar-user dropdown hidden-caret">
                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <div class="avatar-sm">
                        <img src="<?php echo !empty($_SESSION['photo_profile']) ? '../../assets/img/avatars/' . $_SESSION['photo_profile'] : '../../assets/img/avatars/arashmil.jpg'; ?>"
                            alt="..." class="avatar-img rounded-circle" />
                    </div>
                    <span class="profile-username">
                        <span class="op-7">Hi,</span>
                        <span class="fw-bold"><?php echo !empty($_SESSION['nama']) ? $_SESSION['nama'] : '-'; ?></span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-user animated fadeIn">
                    <div class="dropdown-user-scroll scrollbar-outer">
                        <li>
                            <div class="user-box">
                                <div class="avatar-lg">
                                    <img src="<?php echo !empty($_SESSION['photo_profile']) ? '../../assets/img/avatars/' . $_SESSION['photo_profile'] : '../../assets/img/avatars/arashmil.jpg'; ?>"
                                        alt="image profile" class="avatar-img rounded" />
                                </div>
                                <div class="u-text">
                                    <h4><?php echo !empty($_SESSION['nama']) ? $_SESSION['nama'] : '-'; ?></h4>
                                    <p class="text-muted">
                                        <?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : '-'; ?>
                                    </p>
                                    <p class="text-primary">
                                        <?php echo !empty($_SESSION['role']) ? $_SESSION['role'] : '-'; ?>
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="showEditProfileForm()">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </div>
                </ul>
            </li>
        </ul>
    </div>
</nav>
<!-- End Navbar -->

<script>
    // Show Edit Profile Form
    function showEditProfileForm() {
        // Get user data via AJAX instead of using embedded PHP
        $.ajax({
            url: 'profile_handler.php',
            type: 'POST',
            data: {
                action: 'get_profile'
            },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    const userData = response.data;
                    showProfileEditModal(userData);
                } else {
                    showNotification('error', 'Data user tidak ditemukan!');
                }
            },
            error: function () {
                showNotification('error', 'Gagal mengambil data profile!');
            }
        });
    }

    // Show profile edit modal
    function showProfileEditModal(userData) {
        const photoPreview = userData.photo_profile ?
            `<img src="../../assets/img/avatars/${userData.photo_profile}" class="avatar-preview-large" id="profileEditPreview">` :
            '<div class="avatar-placeholder" id="profileEditPreview"><i class="fa fa-user fa-2x"></i></div>';

        swal({
            title: 'Edit Profile',
            content: {
                element: "div",
                attributes: {
                    innerHTML: additionalProfileCSS + `
                    <form id="profileEditForm" enctype="multipart/form-data">
                        <input type="hidden" name="old_photo" value="${userData.photo_profile || ''}">
                        <div class="profile-preview-container">
                            ${photoPreview}
                            <div class="file-input-wrapper">
                                <label class="form-label">Foto Profil</label>
                                <input type="file" class="form-control" name="photo_profile" accept="image/*" onchange="previewProfileImage(this, 'profileEditPreview')">
                                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama" value="${userData.nama || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" value="${userData.email || ''}" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru (Kosongkan jika tidak ingin mengubah)</label>
                            <input type="password" class="form-control" name="password" placeholder="Masukkan password baru">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" class="form-control" value="${userData.role || ''}" readonly>
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
                    text: 'Update Profile',
                    className: 'btn btn-primary',
                    closeModal: false
                }
            }
        }).then((result) => {
            if (result) {
                submitProfileForm();
            }
        });
    }

    // Preview Profile Image
    function previewProfileImage(input, previewId) {
        const file = input.files[0];
        const preview = document.getElementById(previewId);

        if (file) {
            // Validate file
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const fileType = file.type.toLowerCase();

            if (!allowedTypes.includes(fileType)) {
                showNotification('error', 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.');
                input.value = '';
                return;
            }

            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('error', 'Ukuran file terlalu besar! Maksimal 2MB.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.innerHTML = `<img src="${e.target.result}" class="avatar-preview-large">`;
            };
            reader.readAsDataURL(file);
        }
    }

    // Submit Profile Form
    function submitProfileForm() {
        const form = document.getElementById('profileEditForm');
        const formData = new FormData(form);
        formData.append('action', 'update_profile');

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
            url: 'profile_handler.php', // Use dedicated profile handler
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                swal.close();

                if (response.status === 'success') {
                    showNotification('success', response.message);

                    // Update profile display without page reload
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('error', response.message);
                }
            },
            error: function (xhr, status, error) {
                swal.close();
                console.error('Ajax Error:', xhr.responseText);
                showNotification('error', 'Terjadi kesalahan saat memproses data. Silakan coba lagi.');
            }
        });
    }

    // Show Notification Function for Profile
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

    // Additional CSS for Profile Form
    const additionalProfileCSS = `
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
        
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
        }
        
        .text-muted {
            color: #6c757d !important;
            font-size: 12px;
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

    // Handle form submission dengan enter key
    document.addEventListener('keypress', function (e) {
        if (e.target.matches('#profileEditForm input') && e.which === 13) {
            e.preventDefault();
            submitProfileForm();
        }
    });
</script>