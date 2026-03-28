<?php
/**
 * Dynamic robots.txt
 * Just Designs - robots.php (served as /robots.txt via .htaccess rewrite)
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');
?>
User-agent: *
Allow: /
Disallow: /admin/
Disallow: /includes/
Disallow: /ajax/

Sitemap: <?= SITE_URL ?>/sitemap.php
