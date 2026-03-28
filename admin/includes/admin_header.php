<?php
/**
 * Admin shared header/layout
 * Just Designs - admin/includes/admin_header.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isAdminLoggedIn()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

$adminCurrentPage = basename($_SERVER['PHP_SELF']);
$adminFlash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminPageTitle) ? e($adminPageTitle) . ' | ' : '' ?>Admin — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <script>const SITE_URL = <?= json_encode(SITE_URL) ?>;</script>
    <style>
        body { background: #f1f3f5; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-main { flex: 1; padding: 30px; overflow-x: hidden; }
        @media(max-width:768px){ .admin-sidebar{ display:none; } .admin-main{ padding:15px; } }
    </style>
</head>
<body>
<!-- Top Bar -->
<nav class="navbar navbar-dark bg-dark px-3 py-2">
    <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>/admin/">
        <i class="fas fa-shield-alt me-2"></i><?= SITE_NAME ?> Admin
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
        <a href="<?= SITE_URL ?>/" target="_blank" class="text-light text-decoration-none small">
            <i class="fas fa-external-link-alt me-1"></i>View Site
        </a>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-sign-out-alt me-1"></i>Logout
        </a>
    </div>
</nav>

<div class="admin-wrapper">
<!-- Sidebar -->
<div class="admin-sidebar" style="width:220px; flex-shrink:0">
    <nav class="nav flex-column pt-3">
        <a class="nav-link <?= $adminCurrentPage === 'index.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/">
            <i class="fas fa-tachometer-alt"></i>Dashboard
        </a>
        <a class="nav-link <?= $adminCurrentPage === 'upload.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/upload.php">
            <i class="fas fa-upload"></i>Upload Images
        </a>
        <a class="nav-link <?= in_array($adminCurrentPage, ['manage-images.php', 'edit-image.php']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/manage-images.php">
            <i class="fas fa-images"></i>Manage Images
        </a>
        <a class="nav-link <?= $adminCurrentPage === 'users.php' ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/users.php">
            <i class="fas fa-users"></i>Users
        </a>
        <hr class="border-secondary mx-3">
        <a class="nav-link" href="<?= SITE_URL ?>/" target="_blank">
            <i class="fas fa-globe"></i>View Website
        </a>
    </nav>
</div>

<!-- Main Content -->
<div class="admin-main">
<?php if ($adminFlash): ?>
<div class="alert alert-<?= e($adminFlash['type']) ?> alert-dismissible fade show mb-3" role="alert">
    <?= e($adminFlash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
