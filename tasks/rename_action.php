<?php
session_start();
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? '';
if (empty($user_id)) {
    http_response_code(403);
    exit;
}

$id = $_POST['id'] ?? '';
$new_name = trim($_POST['new_name'] ?? '');

if (empty($id) || empty($new_name)) {
    header("Location: ../index.php");
    exit;
}

$stmt = $koneksi->prepare("SELECT type, physical_path, parent_id FROM files WHERE id = ? AND user_id = ?");
$stmt->bind_param("ss", $id, $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($item) {
    if ($item['type'] === 'file') {
        $old_path = $item['physical_path'];
        if (file_exists($old_path)) {
            $dir = dirname($old_path);
            $ext = pathinfo($old_path, PATHINFO_EXTENSION);
            
            $final_name = (pathinfo($new_name, PATHINFO_EXTENSION) === '') ? $new_name . '.' . $ext : $new_name;
            $new_path = $dir . '/' . $final_name;

            if (rename($old_path, $new_path)) {
                $stmt = $koneksi->prepare("UPDATE files SET name = ?, physical_path = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ssss", $new_name, $new_path, $id, $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    } else {
        $stmt = $koneksi->prepare("UPDATE files SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sss", $new_name, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

$redirect_url = "../index.php";
if ($item && !empty($item['parent_id'])) {
    $redirect_url .= "?dir=" . $item['parent_id'];
}
header("Location: " . $redirect_url);
exit;
?>