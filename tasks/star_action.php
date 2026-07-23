<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
    header("Location: " . $referer);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['items'])) {
    $items = json_decode($_POST['items'], true);
    
    if (is_array($items) && count($items) > 0) {
        $stmt = $koneksi->prepare("SELECT is_starred FROM files WHERE id = ? AND user_id = ?");
        $update_stmt = $koneksi->prepare("UPDATE files SET is_starred = ? WHERE id = ? AND user_id = ?");
        
        foreach ($items as $file_id) {
            $stmt->bind_param("ss", $file_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $new_status = $row['is_starred'] ? 0 : 1;
                $update_stmt->bind_param("iss", $new_status, $file_id, $user_id);
                $update_stmt->execute();
            }
        }
        $stmt->close();
        $update_stmt->close();
    }
} elseif (isset($_GET['id'])) {
    $file_id = $_GET['id'];
    $stmt = $koneksi->prepare("SELECT is_starred FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ss", $file_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $new_status = $row['is_starred'] ? 0 : 1;
        $update_stmt = $koneksi->prepare("UPDATE files SET is_starred = ? WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("iss", $new_status, $file_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    $stmt->close();
}

$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';
header("Location: " . $referer);
exit;