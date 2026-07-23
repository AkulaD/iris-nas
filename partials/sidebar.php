<nav class="navbar navbar-expand-lg navbar-custom fixed-top border-bottom">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center fw-semibold text-dark gap-2" href="index.php">
            <i class="bi bi-cloud-arrow-up-fill text-primary fs-4"></i>
            <span>IRiS-NAS</span>
        </a>
        
        <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="mx-auto search-container d-none d-md-flex align-items-center my-2 my-lg-0">
            <form action="index.php" method="GET" class="w-100 m-0 p-0">
                <div class="input-group search-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 shadow-none ps-1" placeholder="Search in IRiS-NAS" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-outline-secondary border-start-0 bg-white text-muted shadow-none" type="button" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="bi bi-sliders"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="d-flex align-items-center gap-3 ms-auto ms-lg-0">
            <i class="bi bi-gear text-muted fs-5 cursor-pointer"></i>
            <a href="tasks/logout.php" class="text-decoration-none">
                <div class="avatar-circle">
                    <span class="fw-medium text-primary small"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></span>
                </div>
            </a>
        </div>
    </div>
</nav>

<div class="sidebar-wrapper collapse d-lg-block border-end" id="sidebarMenu">
    <div class="d-flex flex-column h-100 p-3 pt-4">
        
        <div class="dropdown mb-4">
            <button class="btn btn-new-action d-flex align-items-center justify-content-center gap-2 shadow-sm w-100" type="button" id="dropdownNewButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-plus-lg fs-5"></i>
                <span class="fw-medium">New</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-custom shadow border border-light-subtle w-100 py-1" aria-labelledby="dropdownNewButton">
                <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#createFolderModal"><i class="bi bi-folder-plus text-muted fs-5"></i> Create Folder</a></li>
                <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#uploadFileModal"><i class="bi bi-file-earmark-arrow-up text-muted fs-5"></i> Upload File</a></li>
            </ul>
        </div>

        <ul class="nav flex-column core-navigation gap-1 mb-4">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3" href="index.php">
                    <i class="bi bi-house-door fs-5"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3" href="recent.php">
                    <i class="bi bi-clock-history fs-5"></i>
                    <span>Recent</span>
                </a>
            </li>
        </ul>

        <ul class="nav flex-column core-navigation gap-1 mb-auto">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3" href="starred.php">
                    <i class="bi bi-star fs-5"></i>
                    <span>Starred</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3" href="shared.php">
                    <i class="bi bi-people fs-5"></i>
                    <span>Shared</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-3" href="trash.php">
                    <i class="bi bi-trash3 fs-5"></i>
                    <span>Trash</span>
                </a>
            </li>
        </ul>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            const currentLocation = window.location.pathname.split("/").pop() || "index.php";
            const navLinks = document.querySelectorAll(".core-navigation .nav-link");

            navLinks.forEach(link => {
                link.classList.remove("active");
                if (link.getAttribute("href") === currentLocation) {
                    link.classList.add("active");
                }
            });
        });
        </script>

        <div class="storage-indicator-panel mt-auto pt-3 border-top">
            <div class="d-flex align-items-center gap-2 text-muted mb-2">
                <i class="bi bi-cloud fs-5"></i>
                <span class="small fw-medium">Storage</span>
            </div>
            <div class="progress storage-progress-bar mb-2" role="progressbar" aria-label="Storage Usage Meter" aria-valuenow="12" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar custom-pb-fill" style="width: 12.5%"></div>
            </div>
            <p class="storage-text-metrics text-muted mb-0">1.25 GB of 10 GB used</p>
        </div>

    </div>
</div>

<div class="modal fade" id="createFolderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold text-dark">Create New Folder</h6>
                <button type="button" class="btn-close shadow-none fs-small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="tasks/create_folder.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="parent_id" value="<?php echo isset($_GET['dir']) ? htmlspecialchars($_GET['dir']) : ''; ?>">
                    <input type="text" name="folder_name" class="form-control shadow-none" placeholder="Folder Name" required autocomplete="off">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light-custom px-3 py-1.5 fs-small border text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-1.5 fs-small text-white">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-semibold text-dark">Upload File</h6>
                <button type="button" class="btn-close shadow-none fs-small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="tasks/upload_file.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="parent_id" value="<?php echo isset($_GET['dir']) ? htmlspecialchars($_GET['dir']) : ''; ?>">
                    <input type="file" name="file" class="form-control shadow-none" required>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light-custom px-3 py-1.5 fs-small border text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4 py-1.5 fs-small text-white">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm modal-md-custom">
        <div class="modal-content border-0 filter-modal-box shadow-lg">
            <div class="modal-header px-4 pt-4 border-0 pb-2">
                <h6 class="modal-title fw-semibold text-dark" id="filterModalLabel">Filter Options</h6>
                <button type="button" class="btn-close shadow-none fs-small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pb-4 pt-2">
                <form action="index.php" method="GET" autocomplete="off">
                    <?php if(isset($_GET['search'])): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                    <?php endif; ?>
                    <?php if(isset($_GET['dir'])): ?>
                        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($_GET['dir']); ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-medium">File Type</label>
                        <select name="file_type" class="form-select shadow-none">
                            <option value="All Files" <?php echo (isset($_GET['file_type']) && $_GET['file_type'] == 'All Files') ? 'selected' : ''; ?>>All Files</option>
                            <option value="Folders" <?php echo (isset($_GET['file_type']) && $_GET['file_type'] == 'Folders') ? 'selected' : ''; ?>>Folders</option>
                            <option value="Documents (PDF, Word)" <?php echo (isset($_GET['file_type']) && $_GET['file_type'] == 'Documents (PDF, Word)') ? 'selected' : ''; ?>>Documents (PDF, Word)</option>
                            <option value="Images & Photos" <?php echo (isset($_GET['file_type']) && $_GET['file_type'] == 'Images & Photos') ? 'selected' : ''; ?>>Images & Photos</option>
                            <option value="Videos & Audio" <?php echo (isset($_GET['file_type']) && $_GET['file_type'] == 'Videos & Audio') ? 'selected' : ''; ?>>Videos & Audio</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-medium">Sort Strategy Matrix</label>
                        <select name="sort" class="form-select shadow-none">
                            <option value="Newest Created" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Newest Created') ? 'selected' : (isset($_GET['sort']) ? '' : 'selected'); ?>>Newest Created</option>
                            <option value="Name (A - Z)" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Name (A - Z)') ? 'selected' : ''; ?>>Name (A - Z)</option>
                            <option value="Name (Z - A)" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Name (Z - A)') ? 'selected' : ''; ?>>Name (Z - A)</option>
                            <option value="Oldest Created" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Oldest Created') ? 'selected' : ''; ?>>Oldest Created</option>
                            <option value="Size (Largest)" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Size (Largest)') ? 'selected' : ''; ?>>Size (Largest)</option>
                            <option value="Size (Smallest)" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'Size (Smallest)') ? 'selected' : ''; ?>>Size (Smallest)</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check">
                            <input name="starred" value="true" class="form-check-input shadow-none" type="checkbox" id="checkStarredOnly" <?php echo (isset($_GET['starred']) && $_GET['starred'] == 'true') ? 'checked' : ''; ?>>
                            <label class="form-check-label small text-dark" for="checkStarredOnly">
                                Show Starred items only
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light-custom px-3 py-1.5 fs-small border text-muted" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-custom px-4 py-1.5 fs-small text-white">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>