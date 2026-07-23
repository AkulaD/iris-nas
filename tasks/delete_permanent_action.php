<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

function deleteItemPermanently($koneksi, $itemId, $userId) {
    $stmt = $koneksi->prepare("SELECT `type`, `physical_path` FROM `files` WHERE `id` = ? AND `user_id` = ?");
    $stmt->bind_param("ss", $itemId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) return;

    if ($item['type'] === 'folder') {
        $stmt_child = $koneksi->prepare("SELECT `id` FROM `files` WHERE `parent_id` = ? AND `user_id` = ?");
        $stmt_child->bind_param("ss", $itemId, $userId);
        $stmt_child->execute();
        $res_child = $stmt_child->get_result();
        
        while ($child = $res_child->fetch_assoc()) {
            deleteItemPermanently($koneksi, $child['id'], $userId);
        }
        $stmt_child->close();
    } else {
        if (!empty($item['physical_path']) && file_exists($item['physical_path'])) {
            @unlink($item['physical_path']); 
        }
    }

    $stmt_del = $koneksi->prepare("DELETE FROM `files` WHERE `id` = ? AND `user_id` = ?");
    $stmt_del->bind_param("ss", $itemId, $userId);
    $stmt_del->execute();
    $stmt_del->close();
}

if (!empty($items) && is_array($items)) {
    foreach ($items as $id) {
        deleteItemPermanently($koneksi, $id, $user_id);
    }
}

header("Location: ../trash.php");
exit();