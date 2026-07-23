<?php
session_start();
include '../config.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$file_id = $_POST['id'] ?? '';
$action = $_POST['action'] ?? 'share';

if (empty($file_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
    exit;
}

$stmt = $koneksi->prepare("SELECT share_token FROM files WHERE id = ? AND user_id = ?");
$stmt->bind_param("ss", $file_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan']);
    exit;
}

if ($action === 'unshare') {
    $update = $koneksi->prepare("UPDATE files SET is_shared = 0, share_token = NULL WHERE id = ? AND user_id = ?");
    $update->bind_param("ss", $file_id, $user_id);
    $update->execute();
    $update->close();

    echo json_encode([
        'status' => 'success',
        'message' => 'Berhasil menghapus bagikan'
    ]);
    exit;
} else {
    $token = $row['share_token'];

    if (empty($token)) {
        $token = bin2hex(random_bytes(8));
        
        $update = $koneksi->prepare("UPDATE files SET is_shared = 1, share_token = ? WHERE id = ? AND user_id = ?");
        $update->bind_param("sss", $token, $file_id, $user_id);
        $update->execute();
        $update->close();
    } else {
        $update = $koneksi->prepare("UPDATE files SET is_shared = 1 WHERE id = ? AND user_id = ?");
        $update->bind_param("ss", $file_id, $user_id);
        $update->execute();
        $update->close();
    }

    echo json_encode([
        'status' => 'success',
        'share_token' => $token
    ]);
    exit;
}