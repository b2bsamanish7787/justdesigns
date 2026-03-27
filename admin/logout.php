<?php
/**
 * Admin Logout
 * Just Designs - admin/logout.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();
header('Location: ' . (defined('SITE_URL') ? SITE_URL : '..') . '/admin/login.php');
exit;
