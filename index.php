<?php
/**
 * Home Page - Displays free images grid with Load More
 * Just Designs - index.php
 */

// Load db + functions first so SITE_NAME / SITE_URL constants are available
// for the SEO variables below.  header.php will skip re-loading via require_once.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle       = 'Free & Premium Design Images';
$metaDescription = 'Browse ' . SITE_NAME . ' – a curated gallery of free and premium design images for creatives. Explore illustrations, digital art, wallpapers and more.';
$metaKeywords    = 'free design images, premium design images, graphic design gallery, digital art, creative images, wallpapers, illustrations, just designs';
$canonicalUrl    = SITE_URL . '/';
$ogImage         = SITE_URL . '/assets/img/og-default.jpg';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'WebSite',
    'name'     => SITE_NAME,
    'url'      => SITE_URL . '/',
    'description' => 'Curated gallery of free and premium design images for creatives.',
    'potentialAction' => [
        '@type'       => 'SearchAction',
        'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => SITE_URL . '/?s={search_term_string}'],
        'query-input' => 'required name=search_term_string',
    ],
];
require_once __DIR__ . '/includes/header.php';

// Initial load: 30 free images
$limit = 30;
$images = getAllImages($pdo, 0, $limit);
$totalCount = $pdo->query('SELECT COUNT(*) FROM images')->fetchColumn();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1><i class="fas fa-palette me-3"></i>Just Designs</h1>
        <p class="mt-3">Discover stunning free & premium design images curated for creatives.</p>
        <div class="mt-4">
            <a href="#gallery" class="btn btn-warning btn-lg fw-bold me-3">
                <i class="fas fa-images me-2"></i>Browse Gallery
            </a>
            <?php if (!isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-crown me-2"></i>Get Premium
            </a>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/premium.php" class="btn btn-outline-light btn-lg">
                <i class="fas fa-crown me-2"></i>Premium Images
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container">

<!-- Section Header -->
<div class="section-header" id="gallery">
    <h2>Image Gallery</h2>
    <div class="divider"></div>
    <p class="text-muted mt-2">Browse our collection of beautiful design images</p>
</div>

<!-- CSRF Token for AJAX -->
<meta name="csrf-token" content="<?= getCsrfToken() ?>">

<!-- Image Grid -->
<div class="row g-3" id="image-grid" data-initial-count="<?= count($images) ?>">
    <?php foreach ($images as $img): 
        $thumb = getThumbUrl($img);
    ?>
    <div class="col-6 col-md-4 col-lg-3">
        <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $img['id'] ?>" class="text-decoration-none">
            <div class="image-card card h-100">
                <div class="card-img-wrapper">
                    <span class="badge-type badge <?= $img['image_type'] === 'premium' ? 'badge-premium' : 'badge-free' ?>">
                        <?= $img['image_type'] === 'premium' ? '<i class="fas fa-crown me-1"></i>Premium' : 'Free' ?>
                    </span>
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
    </div>
    <?php endforeach; ?>
</div>

<!-- Load More -->
<?php if (count($images) >= $limit && $totalCount > $limit): ?>
<div id="load-more-wrapper" class="mt-4">
    <button id="load-more-btn" class="btn btn-dark btn-lg" data-type="all">
        <i class="fas fa-plus me-2"></i>Load More
    </button>
</div>
<?php else: ?>
<div class="text-center text-muted py-4">All <?= $totalCount ?> images shown.</div>
<?php endif; ?>

<!-- No JS fallback pagination -->
<noscript>
    <div class="text-center mt-3">
        <a href="?page=2" class="btn btn-outline-dark">Next Page &raquo;</a>
    </div>
</noscript>

</div><!-- /.container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
