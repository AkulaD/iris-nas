<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config.php';

define('BASE_STORAGE_PATH', 'D:' . DIRECTORY_SEPARATOR . 'IRIS_NAS_Data');

function generateUniqueId() {
    return 'file_' . bin2hex(random_bytes(16));
}

function getSafeFileName($name, $path, $is_file = true) {
    $original_name = pathinfo($name, PATHINFO_FILENAME);
    $extension = $is_file ? '.' . pathinfo($name, PATHINFO_EXTENSION) : '';
    if ($extension === '.') {
        $extension = '';
    }
    
    $new_name = $name;
    $counter = 1;
    
    while (file_exists($path . DIRECTORY_SEPARATOR . $new_name)) {
        $new_name = $original_name . ' (' . $counter . ')' . $extension;
        $counter++;
    }
    
    return $new_name;
}

function getPhysicalPathAndDbCheck($koneksi, $parent_id, $user_id) {
    $user_path = BASE_STORAGE_PATH . DIRECTORY_SEPARATOR . $user_id;
    if (!file_exists($user_path)) {
        mkdir($user_path, 0777, true);
    }

    if (empty($parent_id)) {
        return $user_path;
    }

    $current_id = $parent_id;
    $path_parts = [];

    while (!empty($current_id)) {
        $stmt = $koneksi->prepare("SELECT name, parent_id FROM files WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ss", $current_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            array_unshift($path_parts, $row['name']);
            $current_id = $row['parent_id'];
        } else {
            break;
        }
        $stmt->close();
    }

    $full_path = $user_path;
    if (!empty($path_parts)) {
        $full_path .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $path_parts);
    }

    if (!file_exists($full_path)) {
        mkdir($full_path, 0777, true);
    }

    return $full_path;
}
?>