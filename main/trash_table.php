<?php
$user_id = $_SESSION['user_id'] ?? '';
$sort_strategy = $_GET['sort'] ?? 'Newest Created';

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

$sql = "SELECT * FROM `files` WHERE `user_id` = ? AND `deleted_at` IS NOT NULL";
switch ($sort_strategy) {
    case 'Name (A - Z)': $sql .= " ORDER BY `type` ASC, `name` ASC"; break;
    case 'Name (Z - A)': $sql .= " ORDER BY `type` ASC, `name` DESC"; break;
    case 'Oldest Created': $sql .= " ORDER BY `created_at` ASC"; break;
    case 'Size (Largest)': $sql .= " ORDER BY `size` DESC"; break;
    case 'Size (Smallest)': $sql .= " ORDER BY `size` ASC"; break;
    case 'Newest Created':
    default: $sql .= " ORDER BY `type` ASC, `created_at` DESC"; break;
}

$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<div id="custom-context-menu" class="dropdown-menu shadow-sm context-menu-box">
    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-success" href="#" data-action="recover"><i class="bi bi-arrow-counterclockwise"></i> Restore</a>
    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#" data-action="details"><i class="bi bi-info-circle text-muted"></i> Details</a>
    <hr class="dropdown-divider">
    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger" href="#" data-action="delete_permanent"><i class="bi bi-trash3-fill"></i> Delete Permanently</a>
</div>

<div class="drive-grid-container mt-3" id="drive-workspace">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h5 class="text-dark fw-semibold mb-0"><i class="bi bi-trash3 me-2 text-danger"></i>Trash Bin</h5>
        <div>
            <button type="button" id="btn-bulk-recover" class="btn btn-outline-success btn-sm me-2 d-none">
                <i class="bi bi-arrow-counterclockwise"></i> Restore Selected
            </button>
            <button type="button" id="btn-bulk-delete-perm" class="btn btn-danger btn-sm d-none">
                <i class="bi bi-trash3-fill"></i> Delete Selected Permanently
            </button>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="placeholder-container-box border border-dashed rounded d-flex align-items-center justify-content-center p-5 mt-3">
            <div class="text-center py-5">
                <div class="icon-cloud-display-wrapper mb-3 mx-auto">
                    <i class="bi bi-trash text-muted opacity-50 display-4"></i>
                </div>
                <h6 class="fw-medium text-dark mb-1">Trash bin is empty</h6>
                <p class="text-muted small mx-auto max-width-p">Items you delete will appear here and can be restored later.</p>
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
                <?php foreach ($folders as $folder): ?>
                    <div class="drive-item folder-card d-flex align-items-center p-2 gap-2 shadow-sm" 
                        style="cursor: pointer;"
                        data-id="<?= htmlspecialchars($folder['id']); ?>" 
                        data-type="folder" 
                        data-name="<?= htmlspecialchars($folder['name']); ?>"
                        data-size="-" 
                        data-date="<?= htmlspecialchars(date('d M Y, H:i', strtotime($folder['updated_at']))); ?>">
                        <i class="bi bi-folder-fill fs-3 text-secondary ms-2"></i>
                        <span class="text-dark fw-medium small text-truncate pe-2 flex-grow-1"><?= htmlspecialchars($folder['name']); ?></span>

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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>