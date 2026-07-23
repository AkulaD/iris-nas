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
        die("Gagal membuat folder thumbs. Cek permission di Ubuntu.");
    }
}

$thumb_filename = $thumb_dir . '/' . $id . '.jpg';

if (strpos($file['mime_type'], 'video/') === 0) {
    if (!file_exists($thumb_filename)) {
        $video_path = $file['physical_path'];
        $ffmpeg_path = "/usr/bin/ffmpeg"; 
        
        $cmd = escapeshellcmd($ffmpeg_path) . " -y -i " . escapeshellarg($video_path) . " -ss 00:00:01 -vframes 1 " . escapeshellarg($thumb_filename) . " 2>&1";
        
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);
        
        if ($return_var !== 0) {
            die("FFmpeg Error (Code $return_var): " . implode("<br>", $output));
        }
    }

    if (file_exists($thumb_filename)) {
        header("Content-Type: image/jpeg");
        header("Cache-Control: no-cache, must-revalidate"); 
        readfile($thumb_filename);
    } else {
        die("Thumbnail gagal dibuat meski FFmpeg tidak error.");
    }
} elseif (strpos($file['mime_type'], 'image/') === 0) {
    header("Content-Type: " . $file['mime_type']);
    readfile($file['physical_path']);
}
?>