<?php
/**
 * Premium Images Page
 * Just Designs - premium.php
 * Free users see first few images, rest are blurred with subscribe prompt
 */
$pageTitle       = 'Premium Design Images';
$metaDescription = 'Access exclusive, high-quality premium design images on ' . SITE_NAME . '. Subscribe for free to unlock the full collection of premium illustrations and digital art.';
$metaKeywords    = 'premium design images, exclusive illustrations, premium digital art, subscribe design images, high quality wallpapers, just designs premium';
$canonicalUrl    = SITE_URL . '/premium.php';
$ogImage         = SITE_URL . '/assets/img/og-premium.jpg';
$jsonLd = [
    '@context'    => 'https://schema.org',
    '@type'       => 'CollectionPage',
    'name'        => 'Premium Design Images – ' . SITE_NAME,
    'description' => 'Exclusive high-quality premium design images for subscribers.',
    'url'         => SITE_URL . '/premium.php',
    'isPartOf'    => ['@type' => 'WebSite', 'url' => SITE_URL . '/'],
];
require_once __DIR__ . '/includes/header.php';

$allPremium = getImages($pdo, 'premium', 0, 200);
$freePreviewCount = 3; // Number of images free users can see
?>

<!-- Premium Banner -->
<section class="premium-banner">
    <div class="container">
        <h1><i class="fas fa-crown me-3 text-warning"></i>Premium Images</h1>
        <p class="lead mt-2">Exclusive, high-quality design images for subscribers only.</p>
        <?php if (!isSubscriber()): ?>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-warning btn-lg fw-bold mt-3">
            <i class="fas fa-user-plus me-2"></i>Subscribe to Unlock All
        </a>
        <?php endif; ?>
    </div>
</section>

<div class="container">

<!-- CSRF Token for AJAX -->
<meta name="csrf-token" content="<?= getCsrfToken() ?>">

<?php if (!isSubscriber()): ?>
<!-- Subscribe CTA Banner -->
<div class="subscribe-cta mt-4">
    <h3><i class="fas fa-lock me-2"></i>Unlock Full Access</h3>
    <p class="lead mt-2">Subscribe to view all <?= count($allPremium) ?> premium images in full quality.</p>
    <a href="<?= SITE_URL ?>/register.php" class="btn btn-light btn-lg fw-bold mt-2">
        <i class="fas fa-crown me-2"></i>Subscribe Now — It's Free!
    </a>
    <?php if (!isLoggedIn()): ?>
    <p class="mt-3 mb-0 opacity-75">Already a member? <a href="<?= SITE_URL ?>/login.php" class="text-white fw-bold">Login here</a></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="section-header">
    <h2>Premium Collection</h2>
    <div class="divider"></div>
    <p class="text-muted mt-2"><?= count($allPremium) ?> exclusive premium images</p>
</div>

<!-- Image Grid -->
<div class="row g-3" id="image-grid">
    <?php foreach ($allPremium as $index => $img):
        $thumb = getThumbUrl($img);
        $isBlurred = !isSubscriber() && $index >= $freePreviewCount;
    ?>
    <div class="col-6 col-md-4 col-lg-3">
        <?php if ($isBlurred): ?>
        <!-- Blurred for non-subscribers -->
        <div class="image-card card h-100 blurred-card">
            <div class="card-img-wrapper">
                <span class="badge-type badge badge-premium"><i class="fas fa-crown me-1"></i>Premium</span>
                <img src="<?= e($thumb) ?>" alt="Premium Image – Subscribe to view" loading="lazy" decoding="async" width="400" height="300">
                <div class="blur-overlay">
                    <div class="lock-icon"><i class="fas fa-lock"></i></div>
                    <p>Subscribe to access</p>
                    <a href="<?= SITE_URL ?>/register.php" class="btn btn-warning btn-sm mt-2 fw-bold">Subscribe</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Full access -->
        <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $img['id'] ?>" class="text-decoration-none">
            <div class="image-card card h-100">
                <div class="card-img-wrapper">
                    <span class="badge-type badge badge-premium"><i class="fas fa-crown me-1"></i>Premium</span>
                    <img class="lazy" data-src="<?= e($thumb) ?>" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E" alt="<?= e($img['name']) ?>" loading="lazy" decoding="async" width="400" height="300">
                </div>
                <div class="card-body">
                    <p class="card-title"><?= e($img['name']) ?></p>
                    <small class="like-count text-muted">
                        <i class="fas fa-heart text-danger me-1"></i><?= (int)$img['like_count'] ?> likes
                    </small>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (empty($allPremium)): ?>
    <div class="col-12 text-center py-5">
        <i class="fas fa-images fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">No premium images available yet. Check back soon!</p>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
