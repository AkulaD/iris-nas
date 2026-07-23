<?php
include 'config_user.php';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Koneksi gagal");
}

if (!isset($_GET['id'])) {
    die("ID File tidak valid");
}

$id = $_GET['id'];
$stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    die("File tidak ditemukan");
}

$mime = $file['mime_type'];
$streamUrl = "view_file.php?id=" . urlencode($id) . "&stream=1";

if (isset($_GET['stream'])) {
    $filePath = $file['physical_path'];
    if (file_exists($filePath)) {
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        if (strpos($mime, 'image/') === 0) {
            header("Content-Type: $mime");
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=31536000');
            readfile($filePath);
            exit;
        }

        $size = filesize($filePath);
        $time = filemtime($filePath);
        
        $fm = @fopen($filePath, 'rb');
        if (!$fm) {
            header("HTTP/1.1 500 Internal Server Error");
            exit;
        }
        
        $begin = 0;
        $end = $size - 1;
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=\h*(\d+)-(\d*)[\D\h]*/i', $_SERVER['HTTP_RANGE'], $matches)) {
                $begin = intval($matches[1]);
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            header('HTTP/1.1 206 Partial Content');
        } else {
            header('HTTP/1.1 200 OK');
        }
        
        header("Content-Type: $mime");
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Accept-Ranges: bytes');
        header('Content-Length:' . (($end - $begin) + 1));
        if (isset($_SERVER['HTTP_RANGE'])) {
            header("Content-Range: bytes $begin-$end/$size");
        }
        
        fseek($fm, $begin);
        
        $remaining = $end - $begin + 1;
        $bufferSize = 8192;
        
        while (!feof($fm) && $remaining > 0) {
            $bytesToRead = min($bufferSize, $remaining);
            echo fread($fm, $bytesToRead);
            flush();
            $remaining -= $bytesToRead;
        }
        
        fclose($fm);
        exit;
    } else {
        die("File fisik tidak ditemukan di disk");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratinjau - <?= htmlspecialchars($file['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .preview-wrapper {
            min-height: 70vh;
            background-color: #1e1e1e;
            border-radius: 8px;
            overflow: hidden;
        }
        video, audio {
            background-color: #000;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-truncate me-3"><i class="bi bi-file-earmark-arrow-up text-primary me-2"></i><?= htmlspecialchars($file['name']) ?></h4>
            <a href="<?= $streamUrl ?>" download="<?= htmlspecialchars($file['name']) ?>" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-download me-2"></i> Unduh File
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="preview-wrapper d-flex flex-column justify-content-center align-items-center text-white position-relative">
                    <?php if (strpos($mime, 'video/') === 0): ?>
                        <video id="main-video" class="w-100 h-100" style="max-height: 70vh;" controls>
                            <source src="<?= $streamUrl ?>" type="<?= htmlspecialchars($mime) ?>">
                        </video>
                        <div class="position-absolute top-0 end-0 p-3" style="z-index: 10;">
                            <select id="video-speed" class="form-select form-select-sm bg-dark text-white border-secondary">
                                <option value="0.5">0.5x</option>
                                <option value="1" selected>1.0x (Normal)</option>
                                <option value="1.25">1.25x</option>
                                <option value="1.5">1.5x</option>
                                <option value="2">2.0x</option>
                            </select>
                        </div>
                    <?php elseif (strpos($mime, 'audio/') === 0): ?>
                        <div class="w-75 text-center p-4 bg-dark rounded shadow">
                            <i class="bi bi-music-note-beamed display-1 text-primary mb-3 d-block"></i>
                            <audio id="main-audio" class="w-100 mb-3" controls>
                                <source src="<?= $streamUrl ?>" type="<?= htmlspecialchars($mime) ?>">
                            </audio>
                            <div class="d-flex justify-content-center">
                                <select id="audio-speed" class="form-select form-select-sm bg-secondary text-white border-0 w-auto">
                                    <option value="0.5">0.5x</option>
                                    <option value="1" selected>1.0x (Normal)</option>
                                    <option value="1.25">1.25x</option>
                                    <option value="1.5">1.5x</option>
                                    <option value="2">2.0x</option>
                                </select>
                            </div>
                        </div>
                    <?php elseif (strpos($mime, 'image/') === 0): ?>
                        <img src="<?= $streamUrl ?>" class="img-fluid" style="max-height: 70vh; object-fit: contain;">
                    <?php elseif ($mime === 'application/pdf'): ?>
                        <iframe src="<?= $streamUrl ?>" class="w-100 style-iframe" style="height: 70vh;" frameborder="0"></iframe>
                    <?php elseif (in_array($mime, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])): ?>
                        <iframe src="https://docs.google.com/gview?url=<?= urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&stream=1') ?>&embedded=true" class="w-100" style="height: 70vh;" frameborder="0"></iframe>
                    <?php else: ?>
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-file-earmark-lock display-1 mb-3"></i>
                            <p>Pratinjau tidak didukung untuk format file ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="view_file.js"></script>
</body>
</html>