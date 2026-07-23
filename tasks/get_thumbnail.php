<?php
session_start();
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($user_id) || empty($id)) exit;

$stmt = $koneksi->prepare("SELECT physical_path, mime_type FROM files WHERE id = ? AND user_id = ?");
$stmt->bind_param("ss", $id, $user_id);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$file || !file_exists($file['physical_path'])) {
    die("File fisik tidak ditemukan: " . ($file['physical_path'] ?? 'DB Error'));
}

$thumb_dir = __DIR__ . '/../thumbs';

if (!file_exists($thumb_dir)) {
    if (!@mkdir($thumb_dir, 0775, true)) {
        die("Gagal membuat folder thumbs.");
    }
}

$thumb_filename = $thumb_dir . '/' . $id . '.jpg';

if (strpos($file['mime_type'], 'video/') === 0) {
    if (!file_exists($thumb_filename)) {
        if (!function_exists('exec')) {
            die("Fungsi exec() dinonaktifkan di server.");
        }

        $video_path = $file['physical_path'];
        $ffmpeg_path = "/usr/bin/ffmpeg"; 
        
        $cmd = escapeshellcmd($ffmpeg_path) . " -y -ss 00:00:01 -i " . escapeshellarg($video_path) . " -vframes 1 -q:v 2 " . escapeshellarg($thumb_filename) . " 2>&1";
        
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);
        
        if ($return_var !== 0) {
            $log_message = date('Y-m-d H:i:s') . " - ID: $id - Cmd: $cmd - Error: " . implode("\n", $output) . "\n\n";
            file_put_contents(__DIR__ . '/../ffmpeg_error.log', $log_message, FILE_APPEND);
            die("FFmpeg Error Code $return_var");
        }
    }

    if (file_exists($thumb_filename)) {
        header("Content-Type: image/jpeg");
        header("Cache-Control: no-cache, must-revalidate"); 
        readfile($thumb_filename);
        exit;
    } else {
        die("Thumbnail gagal dibuat.");
    }
} elseif (strpos($file['mime_type'], 'image/') === 0) {
    header("Content-Type: " . $file['mime_type']);
    readfile($file['physical_path']);
    exit;
}
?>