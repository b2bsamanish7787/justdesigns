<?php
/**
 * Slug-based image detail router
 * Just Designs - design/index.php
 *
 * Accessed as /design/{slug}  via .htaccess RewriteRule.
 * Looks up the image by slug; if found, delegates to image-detail.php.
 * Old numeric ?id= URLs redirect here with a 301.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    // No slug provided — redirect to home
    redirect(SITE_URL . '/');
}

$image = getImageBySlug($pdo, $slug);

if (!$image) {
    setFlash('danger', 'Image not found.');
    redirect(SITE_URL . '/');
}

// Delegate to image-detail.php by setting the GET parameter and including it
$_GET['id'] = $image['id'];
unset($_GET['_from_slug']);  // remove any stale public param
$_SERVER['JUST_DESIGNS_FROM_SLUG'] = '1';  // server-side flag — prevents the 301 redirect loop
require __DIR__ . '/../image-detail.php';
