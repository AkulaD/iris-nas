<?php
session_start();
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? '';
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
$token = $_POST['download_token'] ?? '';

if (empty($items) && isset($_GET['id'])) {
    $items = [$_GET['id']];
}

if (!empty($items) && !empty($user_id)) {
    $id = $items[0]; 

    $stmt = $koneksi->prepare("SELECT name, physical_path, mime_type, type FROM files WHERE id = ? AND user_id = ? AND type = 'file'");
    $stmt->bind_param("ss", $id, $user_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($file && file_exists($file['physical_path'])) {
        if (!empty($token)) {
            setcookie("download_token", $token, time() + 300, "/");
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file['name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file['physical_path']));
        
        while (ob_get_level()) {
            ob_end_clean();
        }

        ob_clean();
        flush();
        readfile($file['physical_path']);
        exit;

    } else {
        if (!empty($token)) {
            setcookie("download_token", $token, time() + 300, "/");
        }
        echo "File tidak ditemukan di server.";
    }
}
?>