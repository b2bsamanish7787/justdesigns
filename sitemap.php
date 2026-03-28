<?php
/**
 * Dynamic XML Sitemap
 * Just Designs - sitemap.xml (served via PHP)
 * Access via: /sitemap.xml  (or add RewriteRule in .htaccess)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

// Fetch all public images
$stmt = $pdo->query("SELECT id, name, created_at, image_type FROM images ORDER BY created_at DESC");
$images = $stmt->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- Static pages -->
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/premium.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/register.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Free image detail pages -->
    <?php foreach ($images as $img):
        if ($img['image_type'] !== 'free') continue;
    ?>
    <url>
        <loc><?= SITE_URL ?>/image-detail.php?id=<?= (int)$img['id'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($img['created_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>

</urlset>
