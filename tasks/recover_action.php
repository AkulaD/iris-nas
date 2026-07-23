<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];

if (!empty($items) && is_array($items)) {
    $placeholders = implode(',', array_fill(0, count($items), '?'));
    
    $sql = "UPDATE `files` SET `deleted_at` = NULL, `updated_at` = CURRENT_TIMESTAMP WHERE `id` IN ($placeholders) AND `user_id` = ?";
    
    $stmt = $koneksi->prepare($sql);
    
    $types = str_repeat('s', count($items)) . 's';
    $params = array_merge($items, [$user_id]);
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../trash.php");
exit();