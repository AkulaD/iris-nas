<?php
session_start();
include '../config.php';

$file_id = $_GET['id'] ?? '';
$user_id = $_SESSION['user_id'] ?? '';

if (empty($file_id)) {
    http_response_code(400);
    echo 'File ID tidak valid';
    exit;
}

$stmt = $koneksi->prepare("SELECT * FROM files WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("s", $file_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file) {
    http_response_code(404);
    echo 'File tidak ditemukan';
    exit;
}

if ($file['user_id'] !== $user_id && $file['is_shared'] != 1) {
    http_response_code(403);
    echo 'Akses ditolak';
    exit;
}

$file_path = '../' . $file['path'];

if (!file_exists($file_path)) {
    http_response_code(404);
    echo 'File fisik tidak ditemukan di server';
    exit;
}

$mime_type = $file['mime_type'];
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="' . basename($file['name']) . '"');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;