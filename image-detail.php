<?php
/**
 * Image Detail Page
 * Just Designs - image-detail.php
 *
 * All data is fetched BEFORE including the header so SEO / OG meta tags
 * can be populated with real image values.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$imageId = (int)($_GET['id'] ?? 0);
if ($imageId <= 0) {
    setFlash('danger', 'Invalid image ID.');
    redirect(SITE_URL . '/');
}

$image = getImageById($pdo, $imageId);
if (!$image) {
    setFlash('danger', 'Image not found.');
    redirect(SITE_URL . '/');
}

// Premium check: non-subscribers can't view premium detail
if ($image['image_type'] === 'premium' && !isSubscriber()) {
    setFlash('warning', 'This is a premium image. Please subscribe to view it.');
    redirect(SITE_URL . '/premium.php');
}

$files    = getImageFiles($pdo, $imageId);
$liked    = isLoggedIn() ? hasLiked($pdo, (int)$_SESSION['user_id'], $imageId) : false;

// Determine primary display image URL
$primaryUrl = getThumbUrl($image);
if ($files) {
    foreach ($files as $f) {
        if ($f['is_primary']) {
            $primaryUrl = UPLOAD_URL . $f['filename'];
            break;
        }
    }
}

// Related images (same type, excluding current)
$relatedStmt = $pdo->prepare(
    'SELECT i.*, (SELECT filename FROM image_files WHERE image_id = i.id AND is_primary = 1 LIMIT 1) AS primary_file
     FROM images i WHERE i.image_type = ? AND i.id != ? ORDER BY RAND() LIMIT 8'
);
$relatedStmt->execute([$image['image_type'], $imageId]);
$related = $relatedStmt->fetchAll();

// ===== SEO variables =====
$pageTitle       = $image['name'];
$_descBase       = !empty($image['description'])
    ? strip_tags($image['description'])
    : $image['name'] . ' – ' . ucfirst($image['image_type']) . ' design image on ' . SITE_NAME;
$metaDescription = mb_strimwidth($_descBase, 0, 160, '…');
$metaKeywords    = e($image['name']) . ', ' . $image['image_type'] . ' design, design images, ' . SITE_NAME . ', graphic design, illustration';
$canonicalUrl    = SITE_URL . '/image-detail.php?id=' . $imageId;
$ogType          = 'article';
$ogImage         = $primaryUrl;

// JSON-LD – ImageObject schema
$jsonLd = [
    '@context'    => 'https://schema.org',
    '@type'       => 'ImageObject',
    'name'        => $image['name'],
    'description' => $_descBase,
    'contentUrl'  => $primaryUrl,
    'url'         => $canonicalUrl,
    'thumbnailUrl'=> $primaryUrl,
    'datePublished' => date('c', strtotime($image['created_at'])),
    'author'      => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL . '/'],
    'isPartOf'    => ['@type' => 'WebSite', 'url' => SITE_URL . '/'],
];
if (!empty($image['dimensions'])) {
    // e.g. "1920x1080"
    $dims = explode('x', strtolower($image['dimensions']));
    if (count($dims) === 2 && is_numeric($dims[0]) && is_numeric($dims[1])) {
        $jsonLd['width']  = (int)$dims[0];
        $jsonLd['height'] = (int)$dims[1];
    }
}

// Shareable URL (encoded)
$shareUrl   = urlencode($canonicalUrl);
$shareTitle = urlencode($image['name'] . ' | ' . SITE_NAME);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
<meta name="csrf-token" content="<?= getCsrfToken() ?>">

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">Home</a></li>
        <?php if ($image['image_type'] === 'premium'): ?>
        <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/premium.php">Premium</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?= e($image['name']) ?></li>
    </ol>
</nav>

<div class="row">
    <!-- Left: Primary Image + Gallery -->
    <div class="col-lg-8">
        <!-- Primary Image (LCP element – high priority fetch) -->
        <div class="text-center bg-dark rounded-3 p-2 mb-3">
            <img id="primary-detail-img"
                 src="<?= e($primaryUrl) ?>"
                 alt="<?= e($image['name']) ?>"
                 class="detail-primary-img img-fluid"
                 fetchpriority="high"
                 decoding="auto">
        </div>

        <!-- Gallery Thumbnails -->
        <?php if (count($files) > 1): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($files as $file):
                $fileUrl = UPLOAD_URL . $file['filename'];
            ?>
            <img src="<?= e($fileUrl) ?>"
                 class="detail-gallery-thumb <?= $file['is_primary'] ? 'active' : '' ?>"
                 alt="<?= e($image['name']) ?> gallery image"
                 loading="lazy"
                 decoding="async"
                 width="80" height="60">
            <?php endforeach; ?>
        </div>
        <?php elseif (!empty($image['primary_image']) && strpos($image['primary_image'], 'http') === 0): ?>
        <!-- Demo external image thumbnail -->
        <div class="d-flex gap-2 mb-3">
            <img src="<?= e($image['primary_image']) ?>" class="detail-gallery-thumb active" alt="<?= e($image['name']) ?>" loading="lazy" decoding="async" width="80" height="60">
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Details Panel -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 p-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge <?= $image['image_type'] === 'premium' ? 'badge-premium' : 'badge-free' ?> fs-6">
                    <?= $image['image_type'] === 'premium' ? '<i class="fas fa-crown me-1"></i>Premium' : 'Free' ?>
                </span>
                <!-- Like Button -->
                <?php if (isLoggedIn()): ?>
                <button class="like-btn btn btn-link text-danger p-0 <?= $liked ? 'liked' : '' ?>"
                        data-image-id="<?= $imageId ?>"
                        data-auth="yes"
                        title="<?= $liked ? 'Remove from wishlist' : 'Add to wishlist' ?>"
                        aria-label="<?= $liked ? 'Remove from wishlist' : 'Add to wishlist' ?>">
                    <i class="<?= $liked ? 'fas' : 'far' ?> fa-heart fa-xl"></i>
                    <span class="like-count ms-1"><?= (int)$image['like_count'] ?></span>
                </button>
                <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline-danger btn-sm" title="Login to like">
                    <i class="far fa-heart me-1"></i>Like
                </a>
                <?php endif; ?>
            </div>

            <h1 class="h3 fw-bold"><?= e($image['name']) ?></h1>

            <table class="table table-sm mt-3">
                <tr>
                    <th class="text-muted" style="width:45%">Code</th>
                    <td><code><?= e($image['image_code']) ?></code></td>
                </tr>
                <?php if (!empty($image['dimensions'])): ?>
                <tr>
                    <th class="text-muted">Dimensions</th>
                    <td><?= e($image['dimensions']) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th class="text-muted">Type</th>
                    <td class="text-capitalize"><?= e($image['image_type']) ?></td>
                </tr>
                <tr>
                    <th class="text-muted">Likes</th>
                    <td><span class="like-count"><?= (int)$image['like_count'] ?></span></td>
                </tr>
                <tr>
                    <th class="text-muted">Added</th>
                    <td><?= date('M j, Y', strtotime($image['created_at'])) ?></td>
                </tr>
            </table>

            <?php if (!empty($image['description'])): ?>
            <div class="mt-3">
                <h6 class="fw-bold">Description</h6>
                <p class="text-muted"><?= nl2br(e($image['description'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info mt-3 small">
                <i class="fas fa-info-circle me-1"></i>
                <a href="<?= SITE_URL ?>/login.php">Login</a> to like and save images to your wishlist.
            </div>
            <?php endif; ?>

            <!-- ===== Social Share Buttons ===== -->
            <div class="share-box mt-4">
                <h6 class="fw-bold mb-2"><i class="fas fa-share-alt me-1"></i>Share this design</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <!-- Facebook -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>"
                       class="btn btn-share btn-share-facebook"
                       onclick="return openSharePopup(this.href)"
                       rel="noopener noreferrer"
                       aria-label="Share on Facebook"
                       title="Share on Facebook">
                        <i class="fab fa-facebook-f me-1"></i>Facebook
                    </a>
                    <!-- LinkedIn -->
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>"
                       class="btn btn-share btn-share-linkedin"
                       onclick="return openSharePopup(this.href)"
                       rel="noopener noreferrer"
                       aria-label="Share on LinkedIn"
                       title="Share on LinkedIn">
                        <i class="fab fa-linkedin-in me-1"></i>LinkedIn
                    </a>
                    <!-- Copy Link -->
                    <button class="btn btn-share btn-share-copy"
                            onclick="copyPageLink()"
                            title="Copy link"
                            aria-label="Copy page link">
                        <i class="fas fa-link me-1"></i>Copy Link
                    </button>
                </div>
            </div>
            <!-- ===== /Social Share ===== -->
        </div>
    </div>
</div>

<!-- Related Images -->
<?php if (!empty($related)): ?>
<section class="mt-5" aria-label="Related images">
    <h2 class="h4 fw-bold mb-3"><i class="fas fa-th me-2"></i>Related Images</h2>
    <div class="row g-3">
        <?php foreach ($related as $rel):
            $relThumb = getThumbUrl($rel);
        ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="<?= SITE_URL ?>/image-detail.php?id=<?= $rel['id'] ?>" class="text-decoration-none">
                <div class="image-card card h-100">
                    <div class="card-img-wrapper">
                        <span class="badge-type badge <?= $rel['image_type'] === 'premium' ? 'badge-premium' : 'badge-free' ?>">
                            <?= $rel['image_type'] === 'premium' ? '<i class="fas fa-crown me-1"></i>Premium' : 'Free' ?>
                        </span>
                        <img class="lazy"
                             data-src="<?= e($relThumb) ?>"
                             src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                             alt="<?= e($rel['name']) ?>"
                             loading="lazy"
                             decoding="async"
                             width="400" height="300">
                    </div>
                    <div class="card-body">
                        <p class="card-title"><?= e($rel['name']) ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

</div><!-- /.container -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

