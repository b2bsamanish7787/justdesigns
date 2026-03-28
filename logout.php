<?php
/**
 * User Logout
 * Just Designs - logout.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/functions.php';
$_SESSION = [];
session_destroy();
header('Location: ' . SITE_URL . '/login.php');
exit;
