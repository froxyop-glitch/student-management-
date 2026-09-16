<?php
/**
 * College Notes Management System
 * Secure User Logout Handler
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$wasAdmin = (currentRole() === 'admin');

// Perform complete session cleanup
logoutUser();

// Redirect to respective login view
$target = $wasAdmin ? BASE_URL . 'admin/login.php' : BASE_URL . 'user/login.php';
redirect($target, 'You have been securely signed out.', 'info');
