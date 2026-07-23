<?php
$user_id = $_SESSION['user_id'] ?? '';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = $_GET['file_type'] ?? 'All Files';

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

$sql = "SELECT * FROM `files` WHERE `user_id` = ? AND `deleted_at` IS NULL AND `is_shared` = 1";
$params = [$user_id];
$types = "s";

if (!empty($search_query)) {
    $sql .= " AND `name` LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= "s";
}

if ($filter_type == 'Folders') {
    $sql .= " AND `type` = 'folder'";
} elseif ($filter_type == 'Documents (PDF, Word)') {
    $sql .= " AND (`mime_type` LIKE '%pdf%' OR `mime_type` LIKE '%word%' OR `mime_type` LIKE '%text%')";
} elseif ($filter_type == 'Images & Photos') {
    $sql .= " AND `mime_type` LIKE 'image/%'";
} elseif ($filter_type == 'Videos & Audio') {
    $sql .= " AND (`mime_type` LIKE 'video/%' OR `mime_type` LIKE 'audio/%')";
}

$sql .= " ORDER BY `updated_at` DESC";

$stmt = $koneksi->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<div id="custom-context-menu" class="dropdown-menu shadow-sm context-menu-box">
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="copy"><i class="bi bi-copy text-muted"></i> Salin (Ctrl+C)</a>
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="cut"><i class="bi bi-scissors text-muted"></i> Cut (Ctrl+X)</a>
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="paste"><i class="bi bi-clipboard text-muted"></i> Paste (Ctrl+V)</a>
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="download"><i class="bi bi-download text-muted"></i> Download</a>
    <hr class="dropdown-divider">
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="star" id="btn-star-action">
        <i class="bi bi-star text-muted" id="icon-star-action"></i> 
        <span id="text-star-action">Beri Bintang</span>
    </a>
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="share">
        <i class="bi bi-share text-muted" id="icon-share-action"></i> 
        <span id="text-share-action">Bagikan</span>
    </a>    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="rename"><i class="bi bi-cursor-text text-muted"></i> Ganti Nama</a>
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="details"><i class="bi bi-info-circle text-muted"></i> Details</a>
    <hr class="dropdown-divider">
    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="#" data-action="delete"><i class="bi bi-trash3"></i> Hapus (Del)</a>
</div>

<div class="drive-grid-container mt-3" id="drive-workspace">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="index.php" class="text-decoration-none text-primary fw-medium"><i class="bi bi-house-door-fill me-1"></i> Home</a>
            </li>
            <li class="breadcrumb-item active text-dark fw-medium" aria-current="page">
                <i class="bi bi-share-fill text-primary me-1"></i> Shared Files
            </li>
        </ol>
    </nav>

    <?php if (empty($items)): ?>
        <div class="placeholder-container-box border border-dashed rounded d-flex align-items-center justify-content-center p-5 mt-3">
            <div class="text-center py-5">
                <div class="icon-cloud-display-wrapper mb-3 mx-auto">
                    <i class="bi bi-share text-muted opacity-50 display-4"></i>
                </div>
                <h6 class="fw-medium text-dark mb-1">Belum ada file yang dibagikan</h6>
                <p class="text-muted small mx-auto max-width-p">Klik kanan pada file atau folder lalu pilih "Bagikan" untuk membuat tautan publik.</p>
            </div>
        </div>
    <?php else: ?>
        <?php 
        $folders = array_filter($items, fn($i) => $i['type'] === 'folder');
        $files = array_filter($items, fn($i) => $i['type'] === 'file');
        ?>

        <?php if (!empty($folders)): ?>
            <h6 class="text-muted small fw-semibold mb-3">Folders</h6>
            <div class="grid-layout-folder mb-5">
                <?php foreach ($folders as $folder): 
                    $share_link = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' . ($folder['share_token'] ?? '');
                ?>
                    <div class="drive-item folder-card d-flex align-items-center p-2 gap-2 shadow-sm position-relative" 
                        data-id="<?= htmlspecialchars($folder['id']); ?>" 
                        data-type="folder" 
                        data-name="<?= htmlspecialchars($folder['name']); ?>"
                        data-size="-" 
                        data-date="<?= htmlspecialchars(date('d M Y, H:i', strtotime($folder['updated_at']))); ?>"
                        data-starred="<?= $folder['is_starred'] ? '1' : '0'; ?>"
                        data-share-token="<?= htmlspecialchars($folder['share_token'] ?? ''); ?>"
                        data-share-link="iris-nas/<?= htmlspecialchars($folder['share_token'] ?? ''); ?>">
                        
                        <a href="index.php?dir=<?= $folder['id']; ?>" class="d-flex align-items-center gap-2 text-decoration-none flex-grow-1">
                            <i class="bi bi-folder-fill fs-3 text-secondary ms-2"></i>
                            <span class="text-dark fw-medium small text-truncate pe-2"><?= htmlspecialchars($folder['name']); ?></span>
                        </a>
                        <i class="bi bi-share-fill text-primary small me-2" title="Dibagikan"></i>
                        <button class="btn btn-link text-muted p-1 action-menu-btn" type="button" style="z-index: 10;">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($files)): ?>
            <h6 class="text-muted small fw-semibold mb-3">Files</h6>
            <div class="grid-layout-file">
                <?php foreach ($files as $file): 
                    $is_image = strpos($file['mime_type'], 'image/') === 0;
                    $is_video = strpos($file['mime_type'], 'video/') === 0;
                    $view_url = "tasks/view_file.php?id=" . $file['id'];
                ?>
                    <div class="drive-item file-card shadow-sm d-flex flex-column" 
                        data-id="<?= htmlspecialchars($file['id']); ?>" 
                        data-type="file" 
                        data-name="<?= htmlspecialchars($file['name']); ?>" 
                        data-size="<?= htmlspecialchars(formatSizeUnits($file['size'])); ?>" 
                        data-date="<?= htmlspecialchars(date('d M Y, H:i', strtotime($file['updated_at']))); ?>"
                        data-starred="<?= $file['is_starred'] ? '1' : '0'; ?>"
                        data-share-token="<?= htmlspecialchars($file['share_token'] ?? ''); ?>"
                        data-share-link="iris-nas/<?= htmlspecialchars($file['share_token'] ?? ''); ?>">
                        
                        <a href="<?= $view_url ?>" target="_blank" class="file-preview-area position-relative d-block text-decoration-none">
                            <?php if (!empty($file['share_token'])): ?>
                                <i class="bi bi-share-fill star-badge text-primary bg-white rounded-circle p-1 shadow-sm" style="top: 8px; left: 8px; right: auto; font-size: 0.75rem;" title="Dibagikan"></i>
                            <?php endif; ?>
                            <?php if ($file['is_starred']): ?>
                                <i class="bi bi-star-fill star-badge"></i>
                            <?php endif; ?>

                            <?php if ($is_image): ?>
                                <img src="<?= $view_url . '&stream=1' ?>" alt="Preview" class="preview-media" style="object-fit: cover; width: 100%; height: 100%;">
                            <?php elseif ($is_video): ?>
                                <img src="tasks/get_thumbnail.php?id=<?= $file['id'] ?>" alt="Thumbnail" class="preview-media" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="bi bi-file-earmark-text file-icon-fallback text-muted d-flex align-items-center justify-content-center h-100 fs-1"></i>
                            <?php endif; ?>
                        </a>

                        <div class="file-meta-area p-3 border-top bg-white mt-auto d-flex align-items-center gap-2">
                            <?php if ($is_image): ?> <i class="bi bi-image text-primary"></i>
                            <?php elseif ($is_video): ?> <i class="bi bi-film text-danger"></i>
                            <?php else: ?> <i class="bi bi-file-earmark-pdf text-danger"></i>
                            <?php endif; ?>
                            <span class="text-dark small fw-medium text-truncate flex-grow-1"><?= htmlspecialchars($file['name']); ?></span>
                            <button class="btn btn-link text-muted p-0 action-menu-btn" type="button" style="z-index: 10;">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-share me-2"></i>Bagikan File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success py-2 mb-3" id="share-success-alert" style="display: none; font-size: 0.9rem;">
                    <i class="bi bi-check-circle-fill me-1"></i> Data berhasil dibagikan!
                </div>
                <p class="small text-muted mb-2">Tautan publik untuk item: <strong id="share-item-name" class="text-dark"></strong></p>
                <div class="input-group mb-3">
                    <input type="text" id="shareLinkInput" class="form-control" readonly>
                    <button class="btn btn-primary" type="button" id="btnCopyShareLink">
                        <i class="bi bi-clipboard me-1"></i> Salin Link
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="tasks/rename_action.php">
            <div class="modal-header">
                <h5 class="modal-title">Ganti Nama</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="renameItemId">
                <div class="mb-3">
                    <label class="form-label">Nama Baru</label>
                    <input type="text" name="new_name" id="renameItemName" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">File Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Name:</strong> <span id="detail-name"></span></p>
                <p><strong>Type:</strong> <span id="detail-type" class="text-capitalize"></span></p>
                <p><strong>Size:</strong> <span id="detail-size"></span></p>
                <p><strong>Modified:</strong> <span id="detail-date"></span></p>
                
                <div id="detail-share-area" style="display: none;" class="mt-2 pt-2 border-top">
                    <p class="mb-1"><strong>Public Link:</strong></p>
                    <a href="#" id="detail-share-link" target="_blank" class="text-break small fw-medium text-primary"></a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-disabled="modal" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
