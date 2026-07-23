<?php
require_once 'location.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'] ?? '';
    $parent_id = empty($_POST['parent_id']) ? null : $_POST['parent_id'];
    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK && !empty($user_id)) {
        $target_dir = getPhysicalPathAndDbCheck($koneksi, $parent_id, $user_id);
        
        $original_name = basename($file['name']);
        $name_only = pathinfo($original_name, PATHINFO_FILENAME);
        $safe_file_name = getSafeFileName($original_name, $target_dir, true);
        $final_path = $target_dir . DIRECTORY_SEPARATOR . $safe_file_name;
        
        if (move_uploaded_file($file['tmp_name'], $final_path)) {
            $id = generateUniqueId();
            $type = 'file';
            $mime_type = mime_content_type($final_path) ?: $file['type'];
            $size = filesize($final_path);
            
            $stmt = $koneksi->prepare("INSERT INTO files (id, user_id, parent_id, name, type, mime_type, size, physical_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssis", $id, $user_id, $parent_id, $name_only, $type, $mime_type, $size, $final_path);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $redirect_url = empty($parent_id) ? '../index.php' : '../index.php?dir=' . $parent_id;
    header("Location: " . $redirect_url);
    exit;
}
?>