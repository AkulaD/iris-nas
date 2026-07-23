<?php
session_start();
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? '';
if (empty($user_id)) {
    http_response_code(403);
    exit;
}

$action = $_POST['action'] ?? '';
// Jika target_dir adalah "root" atau kosong, paksa menjadi null
$target_dir = (!empty($_POST['target_dir']) && $_POST['target_dir'] !== 'root') ? $_POST['target_dir'] : null;
$items_raw = $_POST['items'] ?? '[]';
$items = json_decode($items_raw, true);

if (empty($action) || empty($items) || !is_array($items)) {
    header("Location: ../index.php" . ($target_dir ? "?dir=" . $target_dir : ""));
    exit;
}

function copyItem($koneksi, $item_id, $target_dir, $user_id) {
    if ($item_id === $target_dir) return;

    $stmt = $koneksi->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ss", $item_id, $user_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) return;

    $new_id = uniqid('file_', true);
    $new_name = $item['name'] . ' (Copy)';
    
    $new_physical_path = $item['physical_path'];
    if ($item['type'] === 'file' && file_exists($item['physical_path'])) {
        $ext = pathinfo($item['physical_path'], PATHINFO_EXTENSION);
        $new_physical_path = dirname($item['physical_path']) . '/' . uniqid('cp_') . (empty($ext) ? '' : '.' . $ext);
        copy($item['physical_path'], $new_physical_path);
    }

    $stmt = $koneksi->prepare("INSERT INTO files (id, user_id, parent_id, name, type, mime_type, size, physical_path, is_starred, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssssisi", $new_id, $user_id, $target_dir, $new_name, $item['type'], $item['mime_type'], $item['size'], $new_physical_path, $item['is_starred']);
    $stmt->execute();
    $stmt->close();

    if ($item['type'] === 'folder') {
        $child_stmt = $koneksi->prepare("SELECT id FROM files WHERE parent_id = ? AND user_id = ?");
        $child_stmt->bind_param("ss", $item_id, $user_id);
        $child_stmt->execute();
        $children = $child_stmt->get_result();
        while ($child = $children->fetch_assoc()) {
            copyItem($koneksi, $child['id'], $new_id, $user_id);
        }
        $child_stmt->close();
    }
}

foreach ($items as $item_id) {
    if ($action === 'cut') {
        $stmt = $koneksi->prepare("UPDATE files SET parent_id = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sss", $target_dir, $item_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'copy') {
        copyItem($koneksi, $item_id, $target_dir, $user_id);
    }
}

header("Location: ../index.php" . ($target_dir ? "?dir=" . $target_dir : ""));
exit;
?>