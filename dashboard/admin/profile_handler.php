<?php
session_start();
include '../../database/koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Handle Profile Update Request ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    header('Content-Type: application/json');

    try {
        $id = $_SESSION['id_user'];
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

        // Update profile
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tb_user SET nama = ?, email = ?, password = ?, photo_profile = ?, updated_at = NOW() WHERE id_user = ?");
            $result = $stmt->execute([$nama, $email, $password, $photo_profile, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE tb_user SET nama = ?, email = ?, photo_profile = ?, updated_at = NOW() WHERE id_user = ?");
            $result = $stmt->execute([$nama, $email, $photo_profile, $id]);
        }

        if ($result) {
            // Update session data
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            $_SESSION['photo_profile'] = $photo_profile;

            echo json_encode(['status' => 'success', 'message' => 'Profile berhasil diupdate!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate profile.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    exit;
}

// Handle get user data for profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_profile') {
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ?");
        $stmt->execute([$_SESSION['id_user']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            echo json_encode(['status' => 'success', 'data' => $user_data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data user tidak ditemukan!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
    exit;
}

// If not a valid request, return error
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;
?>