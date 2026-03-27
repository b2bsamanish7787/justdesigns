<?php
/**
 * Helper functions
 * Just Designs - includes/functions.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is a subscriber
 */
function isSubscriber(): bool {
    return isLoggedIn() && isset($_SESSION['is_subscriber']) && $_SESSION['is_subscriber'] == 1;
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Sanitize output to prevent XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random password
 */
function generateTempPassword(int $length = 10): string {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Send email (basic mail() fallback - replace with PHPMailer for SMTP)
 */
function sendWelcomeEmail(string $to, string $name, string $tempPassword): bool {
    $subject = SITE_NAME . ' - Your Login Details';
    $loginUrl = SITE_URL . '/login.php';
    $message = "Hi $name,\n\n";
    $message .= "Thank you for subscribing to " . SITE_NAME . "!\n\n";
    $message .= "Your temporary password is: $tempPassword\n\n";
    $message .= "Login here: $loginUrl\n\n";
    $message .= "Please change your password after logging in.\n\n";
    $message .= "Best regards,\n" . SITE_NAME . " Team";

    $headers = "From: " . MAIL_FROM . "\r\nX-Mailer: PHP/" . phpversion();

    // Attempt to send email; log failure silently
    $sent = @mail($to, $subject, $message, $headers);
    if (!$sent) {
        error_log("Failed to send email to: $to");
    }
    return $sent;
}

/**
 * Get all images with pagination
 */
function getImages(PDO $pdo, string $type = 'free', int $offset = 0, int $limit = 30): array {
    $stmt = $pdo->prepare(
        'SELECT i.*, 
                (SELECT filename FROM image_files WHERE image_id = i.id AND is_primary = 1 LIMIT 1) AS primary_file,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) AS like_count
         FROM images i
         WHERE i.image_type = ?
         ORDER BY i.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$type, $limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Get all images (both free and premium) with pagination
 */
function getAllImages(PDO $pdo, int $offset = 0, int $limit = 30): array {
    $stmt = $pdo->prepare(
        'SELECT i.*, 
                (SELECT filename FROM image_files WHERE image_id = i.id AND is_primary = 1 LIMIT 1) AS primary_file,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) AS like_count
         FROM images i
         ORDER BY i.created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Get a single image by ID
 */
function getImageById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare(
        'SELECT i.*, 
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) AS like_count
         FROM images i WHERE i.id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get all files for an image entry
 */
function getImageFiles(PDO $pdo, int $imageId): array {
    $stmt = $pdo->prepare('SELECT * FROM image_files WHERE image_id = ? ORDER BY is_primary DESC, id ASC');
    $stmt->execute([$imageId]);
    return $stmt->fetchAll();
}

/**
 * Check if user has liked an image
 */
function hasLiked(PDO $pdo, int $userId, int $imageId): bool {
    $stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND image_id = ?');
    $stmt->execute([$userId, $imageId]);
    return (bool)$stmt->fetch();
}

/**
 * Get user's wishlist
 */
function getUserWishlist(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT i.*, l.created_at AS liked_at,
                (SELECT filename FROM image_files WHERE image_id = i.id AND is_primary = 1 LIMIT 1) AS primary_file
         FROM likes l
         JOIN images i ON l.image_id = i.id
         WHERE l.user_id = ?
         ORDER BY l.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get the thumbnail URL for an image record
 * Prefers uploaded file, falls back to primary_image field
 */
function getThumbUrl(array $image): string {
    if (!empty($image['primary_file'])) {
        return UPLOAD_URL . $image['primary_file'];
    }
    if (!empty($image['primary_image'])) {
        return $image['primary_image'];
    }
    return 'https://picsum.photos/seed/' . $image['id'] . '/400/300';
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * CSRF token helpers
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
