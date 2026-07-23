<?php
require_once 'location.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'] ?? '';
    $folder_name = trim($_POST['folder_name'] ?? '');
    $parent_id = empty($_POST['parent_id']) ? null : $_POST['parent_id'];

    if (!empty($folder_name) && !empty($user_id)) {
        $target_dir = getPhysicalPathAndDbCheck($koneksi, $parent_id, $user_id);
        
        $safe_folder_name = getSafeFileName($folder_name, $target_dir, false);
        $final_path = $target_dir . DIRECTORY_SEPARATOR . $safe_folder_name;
        
        if (mkdir($final_path, 0777, true)) {
            $id = generateUniqueId();
            $type = 'folder';
            $size = 0;
            
            $stmt = $koneksi->prepare("INSERT INTO files (id, user_id, parent_id, name, type, size, physical_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $id, $user_id, $parent_id, $safe_folder_name, $type, $size, $final_path);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    $redirect_url = empty($parent_id) ? '../index.php' : '../index.php?dir=' . $parent_id;
    header("Location: " . $redirect_url);
    exit;
}
?>