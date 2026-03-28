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

// Fetch all images (both free and premium) with primary image URL
$stmt = $pdo->prepare(
    'SELECT i.id, i.name, i.slug, i.alt_text, i.tags, i.created_at, i.image_type,
            i.primary_image,
            (SELECT CONCAT(?, f.filename)
             FROM image_files f
             WHERE f.image_id = i.id AND f.is_primary = 1
             LIMIT 1) AS primary_file
     FROM images i
     ORDER BY i.created_at DESC'
);
$stmt->execute([UPLOAD_URL]);
$images = $stmt->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

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

    <!-- Image detail pages -->
    <?php foreach ($images as $img):
        // Build canonical URL: prefer slug-based, fall back to ?id=
        $loc = !empty($img['slug'])
            ? SITE_URL . '/design/' . rawurlencode($img['slug'])
            : SITE_URL . '/image-detail.php?id=' . (int)$img['id'];

        // Determine the best image URL for the sitemap
        $imageUrl = $img['primary_file'] ?: $img['primary_image'] ?: '';

        // Caption: tags if available, else name
        $caption = !empty($img['tags'])
            ? htmlspecialchars($img['name'] . '. ' . $img['tags'], ENT_XML1, 'UTF-8')
            : htmlspecialchars($img['name'], ENT_XML1, 'UTF-8');

        $title = htmlspecialchars(
            !empty($img['alt_text']) ? $img['alt_text'] : $img['name'],
            ENT_XML1, 'UTF-8'
        );

        $priority = $img['image_type'] === 'premium' ? '0.5' : '0.7';
    ?>
    <url>
        <loc><?= htmlspecialchars($loc, ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($img['created_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority><?= $priority ?></priority>
        <?php if ($imageUrl): ?>
        <image:image>
            <image:loc><?= htmlspecialchars($imageUrl, ENT_XML1, 'UTF-8') ?></image:loc>
            <image:title><?= $title ?></image:title>
            <image:caption><?= $caption ?></image:caption>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endforeach; ?>

</urlset>
