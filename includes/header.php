<?php
/**
 * Site-wide HTML header
 * Just Designs - includes/header.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= SITE_NAME ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Site config for JavaScript -->
    <script>const SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>/">
            <i class="fas fa-palette me-2"></i><?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'premium.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/premium.php">
                        <i class="fas fa-crown me-1 text-warning"></i>Premium
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'wishlist.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/wishlist.php">
                        <i class="fas fa-heart me-1"></i>Wishlist
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= e($_SESSION['user_name'] ?? 'Account') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/wishlist.php"><i class="fas fa-heart me-2"></i>My Wishlist</a></li>
                            <?php if (!empty($_SESSION['must_change_password'])): ?>
                            <li><a class="dropdown-item text-warning" href="<?= SITE_URL ?>/change-password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-warning btn-sm text-dark px-3 ms-2" href="<?= SITE_URL ?>/register.php">
                            <i class="fas fa-user-plus me-1"></i>Subscribe
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<?php if (isLoggedIn() && !empty($_SESSION['must_change_password'])): ?>
<div class="container mt-2">
    <div class="alert alert-warning py-2">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Please <a href="<?= SITE_URL ?>/change-password.php" class="fw-bold">change your temporary password</a> to secure your account.
    </div>
</div>
<?php endif; ?>
