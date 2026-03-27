<?php
/**
 * AJAX - Like / Unlike an image
 * Just Designs - ajax/like.php
 * Returns JSON: { success, action, count, message }
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to like images.']);
    exit;
}

// Verify CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$imageId = (int)($_POST['image_id'] ?? 0);
if ($imageId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid image.']);
    exit;
}

// Check image exists
$imgCheck = $pdo->prepare('SELECT id FROM images WHERE id = ?');
$imgCheck->execute([$imageId]);
if (!$imgCheck->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Image not found.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (hasLiked($pdo, $userId, $imageId)) {
    // Unlike
    $stmt = $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND image_id = ?');
    $stmt->execute([$userId, $imageId]);
    $action = 'unliked';
    $msg = 'Removed from wishlist.';
} else {
    // Like
    $stmt = $pdo->prepare('INSERT INTO likes (user_id, image_id) VALUES (?, ?)');
    $stmt->execute([$userId, $imageId]);
    $action = 'liked';
    $msg = 'Added to wishlist!';
}

// Get updated count
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE image_id = ?');
$countStmt->execute([$imageId]);
$count = (int)$countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'action'  => $action,
    'count'   => $count,
    'message' => $msg,
]);
