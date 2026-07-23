<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'partials/header.php';

require_once 'partials/sidebar.php';

include 'config.php';
?>

<main class="main-content-canvas">
    <div class="container-fluid px-2 px-md-4 py-4">
        
        <?php require_once 'main/starred_table.php'; ?>

    </div>
</main>

<?php
require_once 'partials/footer.php';
?>