<?php
/**
 * AJAX - Load More Images
 * Just Designs - ajax/load_more.php
 * Returns JSON: { html, count, has_more }
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$offset = max(0, (int)($_GET['offset'] ?? 0));
$type   = $_GET['type'] ?? 'all';
$limit  = 50;

if ($type === 'free') {
    $images = getImages($pdo, 'free', $offset, $limit);
    $total  = (int)$pdo->query("SELECT COUNT(*) FROM images WHERE image_type='free'")->fetchColumn();
} elseif ($type === 'premium') {
    $images = getImages($pdo, 'premium', $offset, $limit);
    $total  = (int)$pdo->query("SELECT COUNT(*) FROM images WHERE image_type='premium'")->fetchColumn();
} else {
    $images = getAllImages($pdo, $offset, $limit);
    $total  = (int)$pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
}

$html = '';
foreach ($images as $img) {
    $thumb = getThumbUrl($img);
    $badgeClass  = $img['image_type'] === 'premium' ? 'badge-premium' : 'badge-free';
    $badgeLabel  = $img['image_type'] === 'premium'
        ? '<i class="fas fa-crown me-1"></i>Premium' : 'Free';

    $html .= '<div class="col-6 col-md-4 col-lg-3">';
    $html .= '<a href="' . SITE_URL . '/image-detail.php?id=' . $img['id'] . '" class="text-decoration-none">';
    $html .= '<div class="image-card card h-100">';
    $html .= '<div class="card-img-wrapper">';
    $html .= '<span class="badge-type badge ' . $badgeClass . '">' . $badgeLabel . '</span>';
    $html .= '<img class="lazy" data-src="' . htmlspecialchars($thumb, ENT_QUOTES) . '" ';
    $html .= 'src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E" ';
    $html .= 'alt="' . htmlspecialchars($img['name'], ENT_QUOTES) . '" loading="lazy" decoding="async" width="400" height="300">';
    $html .= '</div>';
    $html .= '<div class="card-body">';
    $html .= '<p class="card-title">' . htmlspecialchars($img['name'], ENT_QUOTES) . '</p>';
    $html .= '<small class="like-count text-muted"><i class="fas fa-heart text-danger me-1"></i>' . (int)$img['like_count'] . ' likes</small>';
    $html .= '</div></div></a></div>';
}

echo json_encode([
    'html'     => $html,
    'count'    => count($images),
    'has_more' => ($offset + count($images)) < $total,
]);
