<?php
session_start();
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? '';
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if (!empty($user_id) && !empty($items) && is_array($items)) {
    $stmt = $koneksi->prepare("UPDATE files SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
    
    foreach ($items as $id) {
        $stmt->bind_param("ss", $id, $user_id);
        $stmt->execute();
    }
    
    $stmt->close();
}

$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $redirect_url);
exit;
?>