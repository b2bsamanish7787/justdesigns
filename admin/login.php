<?php
/**
 * Admin Login
 * Just Designs - admin/login.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                header('Location: ' . SITE_URL . '/admin/');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="bg-dark">
<div class="auth-wrapper py-5">
    <div class="container">
        <div class="auth-card card mx-auto" style="max-width:400px">
            <div class="auth-header" style="background: linear-gradient(135deg,#1a1a2e,#e94560)">
                <i class="fas fa-shield-alt fa-2x mb-2"></i>
                <h3>Admin Login</h3>
                <p class="mb-0 opacity-75"><?= SITE_NAME ?> Administration</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" required autofocus
                                   value="<?= e($_POST['username'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold py-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Login to Admin
                    </button>
                </form>
                <p class="text-center text-muted small mt-3">Default: admin / admin123</p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
