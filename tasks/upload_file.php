<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'location.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'] ?? '';
    $parent_id = empty($_POST['parent_id']) ? null : $_POST['parent_id'];
    $file = $_FILES['file'];

    if (empty($user_id)) {
        die("Error: Sesi pengguna tidak valid. Silakan login kembali.");
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                die("Error: Ukuran file melebihi batas 'upload_max_filesize' di php.ini server.");
            case UPLOAD_ERR_FORM_SIZE:
                die("Error: Ukuran file melebihi batas MAX_FILE_SIZE yang ditentukan di form HTML.");
            case UPLOAD_ERR_PARTIAL:
                die("Error: File hanya terunggah sebagian. Silakan coba lagi.");
            case UPLOAD_ERR_NO_FILE:
                die("Error: Tidak ada file yang diunggah.");
            case UPLOAD_ERR_NO_TMP_DIR:
                die("Error: Folder sementara (tmp) tidak ditemukan di Ubuntu Server.");
            case UPLOAD_ERR_CANT_WRITE:
                die("Error: Gagal menulis file ke disk. Periksa sisa ruang atau izin disk.");
            default:
                die("Error: Terjadi kesalahan upload yang tidak diketahui (Kode: " . $file['error'] . ").");
        }
    }

    $target_dir = getPhysicalPathAndDbCheck($koneksi, $parent_id, $user_id);
    
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0775, true)) {
            die("Error: Folder tujuan tidak ditemukan dan gagal dibuat di server: " . htmlspecialchars($target_dir));
        }
    }

    if (!is_writable($target_dir)) {
        die("Error: Ubuntu Server menolak akses tulis (Permission Denied) pada folder: " . htmlspecialchars($target_dir) . ". Jalankan 'sudo chown -R www-data:www-data' pada folder tersebut.");
    }

    $original_name = basename($file['name']);
    $name_only = pathinfo($original_name, PATHINFO_FILENAME);
    $safe_file_name = getSafeFileName($original_name, $target_dir, true);
    
    $final_path = rtrim($target_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safe_file_name;
    
    if (move_uploaded_file($file['tmp_name'], $final_path)) {
        $id = generateUniqueId();
        $type = 'file';
        
        $mime_type = !empty($file['type']) ? $file['type'] : 'application/octet-stream'; 

        if (function_exists('mime_content_type')) {
            $detected_mime = @mime_content_type($final_path);
            if ($detected_mime) {
                $mime_type = $detected_mime;
            }
        } 

        if (class_exists('finfo')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected_mime_finfo = @finfo_file($finfo, $final_path);
                if ($detected_mime_finfo) {
                    $mime_type = $detected_mime_finfo;
                }
                finfo_close($finfo);
            }
        }
        
        $size = filesize($final_path);
        
        $stmt = $koneksi->prepare("INSERT INTO files (id, user_id, parent_id, name, type, mime_type, size, physical_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssssis", $id, $user_id, $parent_id, $name_only, $type, $mime_type, $size, $final_path);
            if (!$stmt->execute()) {
                unlink($final_path);
                die("Error Database: Gagal menyimpan data file ke database. " . $stmt->error);
            }
            $stmt->close();
        } else {
            unlink($final_path);
            die("Error Database: Gagal mempersiapkan statement SQL. " . $koneksi->error);
        }
    } else {
        die("Error: Gagal memindahkan file dari folder temporary ke folder tujuan. Periksa konfigurasi open_basedir atau izin akses web server.");
    }
    
    $redirect_url = empty($parent_id) ? '../index.php' : '../index.php?dir=' . $parent_id;
    header("Location: " . $redirect_url);
    exit;
} else {
    die("Error: Akses tidak sah.");
}
?>