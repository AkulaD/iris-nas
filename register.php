<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | IRiS-NAS</title>
    <link rel="stylesheet" href="node_modules/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="node_modules/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="data/register.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="register-container">
        <div class="card register-card border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="logo-wrapper mb-3">
                        <i class="bi bi-cloud-arrow-up-fill text-primary fs-2"></i>
                    </div>
                    <h4 class="fw-semibold text-dark mb-1">Create account</h4>
                    <p class="text-muted small">for IRiS-NAS private cloud storage</p>
                </div>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i>
                        <div class="small"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div class="small"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    </div>
                <?php endif; ?>
                <form action="tasks/register_process.php" method="POST" autocomplete="off">
                    <div class="form-floating mb-3">
                        <input type="text" name="username" class="form-control" id="username" placeholder="Username" required>
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" required>
                        <label for="email">Email address</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm Password" required>
                        <label for="confirm_password">Confirm password</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-normal btn-register">Register</button>
                </form>
                <div class="text-center mt-4">
                    <span class="text-muted small">Already have an account? </span>
                    <a href="login.php" class="small text-decoration-none fw-normal text-primary">Sign in</a>
                </div>
            </div>
        </div>
    </div>
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="data/app.js"></script>
</body>
</html>