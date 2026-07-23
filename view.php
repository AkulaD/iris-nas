<?php
require_once 'config.php';

$token = $_GET['id'] ?? '';
$file_id = $_GET['file_id'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($token)) {
    header("HTTP/1.0 404 Not Found");
    echo "<title>404 Not Found</title><div style='text-align:center; margin-top:100px;'><h2>Tautan tidak valid atau kosong.</h2></div>";
    exit;
}

$stmt = $koneksi->prepare("SELECT * FROM `files` WHERE `share_token` = ? AND `deleted_at` IS NULL");
$stmt->bind_param("s", $token);
$stmt->execute();
$main_item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$main_item) {
    header("HTTP/1.0 404 Not Found");
    include_layout_error("Tautan Tidak Ditemukan", "Tautan publik mungkin sudah dihapus oleh pemiliknya atau telah kedaluwarsa.");
    exit;
}

$current_item = $main_item;
if (!empty($file_id) && $main_item['type'] === 'folder') {
    $stmt_child = $koneksi->prepare("SELECT * FROM `files` WHERE `id` = ? AND `parent_id` = ? AND `deleted_at` IS NULL");
    $stmt_child->bind_param("ss", $file_id, $main_item['id']);
    $stmt_child->execute();
    $child_item = $stmt_child->get_result()->fetch_assoc();
    $stmt_child->close();

    if ($child_item) {
        $current_item = $child_item;
    } else {
        die("File tidak ditemukan di dalam folder ini.");
    }
}

if ($current_item['type'] === 'file') {
    $file_path = $current_item['physical_path'];
    
    if ($action === 'download' || $action === 'raw') {
        if (!empty($file_path) && file_exists($file_path)) {
            if (ob_get_level()) ob_end_clean();
            
            header('Content-Type: ' . ($current_item['mime_type'] ?? 'application/octet-stream'));
            header('Content-Length: ' . $current_item['size']);
            
            if ($action === 'download') {
                header('Content-Disposition: attachment; filename="' . basename($current_item['name']) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
            }
            
            readfile($file_path);
            exit;
        } else {
            die("Penyimpanan fisik file tidak ditemukan di server.");
        }
    }
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes > 1 ? $bytes . ' bytes' : ($bytes == 1 ? '1 byte' : '0 bytes');
}

function include_layout_error($title, $msg) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'.$title.'</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"></head><body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;"><div class="card shadow-sm text-center p-5" style="max-width: 500px;"><i class="bi bi-exclamation-triangle text-warning display-1 mb-3"></i><h4 class="fw-bold">'.$title.'</h4><p class="text-muted small">'.$msg.'</p><a href="index.php" class="btn btn-primary mt-2 btn-sm"><i class="bi bi-house me-1"></i> Kembali ke Beranda</a></div></body></html>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRIS-NAS - Share</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }
        .public-navbar { background-color: #ffffff; border-bottom: 1px solid #e3e6f0; }
        .preview-box { background: #ffffff; border-radius: 12px; border: 1px solid #e3e6f0; overflow: hidden; }
        .media-render { max-width: 100%; max-height: 450px; object-fit: contain; display: block; margin: 0 auto; border-radius: 6px; }
        .folder-item:hover { background-color: #f1f3f5; cursor: pointer; }
        a { text-decoration: none; }
    </style>
</head>
<body>

<nav class="navbar public-navbar py-3 mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand mb-0 h1 fw-bold text-primary d-flex align-items-center gap-2">
            <i class="bi bi-hdd-network-fill"></i> IRIS-NAS
        </span>
        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill small"><i class="bi bi-shield-check me-1"></i> Tautan Publik Aman</span>
    </div>
</nav>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <?php if ($main_item['type'] === 'file'): 
                $is_image = strpos($main_item['mime_type'], 'image/') === 0;
                $is_video = strpos($main_item['mime_type'], 'video/') === 0;
                $is_audio = strpos($main_item['mime_type'], 'audio/') === 0;
                $raw_url = "view.php?id=" . urlencode($token) . "&action=raw";
                $dl_url = "view.php?id=" . urlencode($token) . "&action=download";
            ?>
                <div class="preview-box p-4 shadow-sm text-center">
                    <div class="mb-4 pt-3">
                        <?php if ($is_image): ?>
                            <img src="<?= $raw_url; ?>" class="media-render shadow-sm" alt="Public Preview">
                        <?php elseif ($is_video): ?>
                            <video src="<?= $raw_url; ?>" controls class="media-render shadow-sm w-100"></video>
                        <?php elseif ($is_audio): ?>
                            <div class="p-4 bg-light rounded shadow-sm border mb-2 max-width-p mx-auto">
                                <i class="bi bi-music-note-beamed text-danger display-4 d-block mb-3"></i>
                                <audio src="<?= $raw_url; ?>" controls class="w-100"></audio>
                            </div>
                        <?php else: ?>
                            <div class="py-5">
                                <i class="bi bi-file-earmark-text text-secondary display-1 opacity-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4 opacity-75">

                    <div class="d-md-flex align-items-center justify-content-between text-start px-md-3">
                        <div class="mb-3 mb-md-0">
                            <h5 class="fw-bold text-dark mb-1 text-truncate" style="max-width: 450px;">
                                <?= htmlspecialchars($main_item['name']); ?>
                            </h5>
                            <p class="text-muted small mb-0 d-flex flex-wrap gap-3">
                                <span><i class="bi bi-hdd me-1"></i> Ukuran: <strong><?= formatSizeUnits($main_item['size']); ?></strong></span>
                                <span><i class="bi bi-calendar3 me-1"></i> Diperbarui: <strong><?= date('d M Y, H:i', strtotime($main_item['updated_at'])); ?></strong></span>
                            </p>
                        </div>
                        <div>
                            <a href="<?= $dl_url; ?>" class="btn btn-primary btn-lg px-4 shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-cloud-arrow-down-fill fs-5"></i> Unduh File
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: 
                $stmt_content = $koneksi->prepare("SELECT * FROM `files` WHERE `parent_id` = ? AND `deleted_at` IS NULL ORDER BY `type` ASC, `name` ASC");
                $stmt_content->bind_param("s", $main_item['id']);
                $stmt_content->execute();
                $contents = $stmt_content->get_result();
                $stmt_content->close();
            ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-folder-fill text-warning fs-2"></i>
                    <div>
                        <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($main_item['name']); ?></h4>
                        <p class="text-muted small mb-0">Folder ini dibagikan secara publik</p>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light text-muted small uppercase">
                                <tr>
                                    <th scope="col" class="ps-4 py-3">Nama</th>
                                    <th scope="col" class="py-3">Ukuran</th>
                                    <th scope="col" class="py-3">Modifikasi</th>
                                    <th scope="col" class="text-end pe-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($contents->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-folder2-open display-4 opacity-25 d-block mb-2"></i>
                                            Folder ini kosong atau tidak memiliki sub-file publik.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php while ($row = $contents->fetch_assoc()): 
                                        $row_is_img = strpos($row['mime_type'] ?? '', 'image/') === 0;
                                        $row_is_vid = strpos($row['mime_type'] ?? '', 'video/') === 0;
                                    ?>
                                        <tr class="folder-item">
                                            <td class="ps-4 py-3 fw-medium text-dark">
                                                <?php if ($row['type'] === 'folder'): ?>
                                                    <i class="bi bi-folder-fill text-secondary me-2 fs-5"></i>
                                                    <?= htmlspecialchars($row['name']); ?>
                                                <?php else: ?>
                                                    <?php if ($row_is_img): ?><i class="bi bi-image text-primary me-2 fs-5"></i>
                                                    <?php elseif ($row_is_vid): ?><i class="bi bi-film text-danger me-2 fs-5"></i>
                                                    <?php else: ?><i class="bi bi-file-earmark-text text-muted me-2 fs-5"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($row['name']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small">
                                                <?= $row['type'] === 'folder' ? '-' : formatSizeUnits($row['size']); ?>
                                            </td>
                                            <td class="text-muted small">
                                                <?= date('d M Y', strtotime($row['updated_at'])); ?>
                                            </td>
                                            <td class="text-end pe-4 py-3">
                                                <?php if ($row['type'] === 'file'): ?>
                                                    <a href="view.php?id=<?= urlencode($token); ?>&file_id=<?= $row['id']; ?>&action=download" 
                                                    class="btn btn-outline-primary btn-sm rounded-2 px-3" title="Unduh">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border px-2 py-1 small">Nested Subfolder</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>