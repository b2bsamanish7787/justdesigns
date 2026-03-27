<?php
/**
 * Database connection using PDO
 * Just Designs - includes/db.php
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'justdesigns');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site settings
define('SITE_NAME', 'Just Designs');
define('SITE_URL', 'http://localhost/justdesigns');
define('MAIL_FROM', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'justdesigns.com'));
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Don't expose db errors in production
    error_log('DB Connection failed: ' . $e->getMessage());
    die('Database connection failed. Please try again later.');
}
