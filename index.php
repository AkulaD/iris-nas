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
        
        <div class="d-flex flex-column mb-4">
            <h4 class="fw-semibold text-dark mb-1">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h4>
            <p class="text-muted small mb-0">Manage your private documents and data files smoothly within your localized cloud infrastructure storage environment.</p>
        </div>

        <?php require_once 'main/main_table.php'; ?>

    </div>
</main>

<?php
require_once 'partials/footer.php';
?>