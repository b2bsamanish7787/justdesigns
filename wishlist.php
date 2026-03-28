<?php
/**
 * Wishlist / Favorites Page
 * Just Designs - wishlist.php
 */
$pageTitle = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';

if (!isLoggedIn()) {
    setFlash('warning', 'Please login to view your wishlist.');
    redirect(SITE_URL . '/login.php?redirect=' . urlencode(SITE_URL . '/wishlist.php'));
}

$wishlist = getUserWishlist($pdo, (int)$_SESSION['user_id']);
?>

<div class="container mt-4">

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="fw-bold mb-0"><i class="fas fa-heart text-danger me-2"></i>My Wishlist</h2>
    <span class="badge bg-dark fs-6"><?= count($wishlist) ?> image<?= count($wishlist) !== 1 ? 's' : '' ?></span>
</div>

<meta name="csrf-token" content="<?= getCsrfToken() ?>">

<?php if (empty($wishlist)): ?>
<div class="text-center py-5">
    <i class="far fa-heart fa-4x text-muted mb-4 d-block"></i>
    <h4 class="text-muted">Your wishlist is empty</h4>
    <p class="text-muted">Browse images and click the heart button to add them here.</p>
    <a href="<?= SITE_URL ?>/" class="btn btn-dark btn-lg mt-2">
        <i class="fas fa-images me-2"></i>Browse Gallery
    </a>
</div>
<?php else: ?>

<!-- Grid view -->
<div class="row g-3">
    <?php foreach ($wishlist as $img):
        $thumb = getThumbUrl($img);
    ?>
    <div class="col-6 col-md-4 col-lg-3" id="wishlist-card-<?= $img['id'] ?>">
        <div class="image-card card h-100 position-relative">
            <div class="card-img-wrapper">
                <span class="badge-type badge <?= $img['image_type'] === 'premium' ? 'badge-premium' : 'badge-free' ?>">
                    <?= $img['image_type'] === 'premium' ? '<i class="fas fa-crown me-1"></i>Premium' : 'Free' ?>
                </span>
                <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $img['id'] ?>">
                    <img class="lazy" data-src="<?= e($thumb) ?>" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" alt="<?= e($img['name']) ?>">
                </a>
            </div>
            <div class="card-body d-flex align-items-center justify-content-between">
                <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $img['id'] ?>" class="text-decoration-none text-dark">
                    <p class="card-title mb-0"><?= e($img['name']) ?></p>
                </a>
                <button class="like-btn btn btn-link text-danger p-0 liked"
                        data-image-id="<?= $img['id'] ?>"
                        data-auth="yes"
                        title="Remove from wishlist">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

</div><!-- /.container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
