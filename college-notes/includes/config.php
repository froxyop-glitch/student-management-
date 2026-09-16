<?php
/**
 * College Notes Management System
 * Global Configuration File
 */

// Prevent direct script access if called alone
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access denied.');
}

// --------------------------------------------------------
// Application Settings
// --------------------------------------------------------
define('APP_NAME', 'College Notes Management System');
define('APP_SHORT_NAME', 'CollegeNotes');
define('APP_VERSION', '1.0.0');

// Set default timezone
date_default_timezone_set('UTC');

// --------------------------------------------------------
// Database Configuration
// --------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_notes');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --------------------------------------------------------
// URL and Path Configuration
// Auto-detect base URL for flexible root/subfolder deployments
// --------------------------------------------------------
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Detect if running inside a subfolder like /college-notes
$subDir = '';
if (preg_match('#^(/[^/]+)#', $scriptDir, $matches)) {
    // If running in subfolder (e.g. /college-notes or /scratch/college-notes)
    // Find project root folder name
    $rootName = 'college-notes';
    $pos = strpos($scriptDir, '/' . $rootName);
    if ($pos !== false) {
        $subDir = substr($scriptDir, 0, $pos + strlen($rootName) + 1);
    } else {
        $subDir = rtrim($scriptDir, '/') . '/';
    }
} else {
    $subDir = '/';
}

define('BASE_URL', rtrim($protocol . $host . $subDir, '/') . '/');
define('BASE_PATH', str_replace('\\', '/', realpath(__DIR__ . '/..')) . '/');
define('UPLOAD_DIR', BASE_PATH . 'assets/uploads/notes/');

// --------------------------------------------------------
// Session and Security Settings
// --------------------------------------------------------
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('MAX_LOGIN_ATTEMPTS', 5); // Max failed login attempts
define('LOCKOUT_TIME', 900);     // 15 minutes lockout in seconds

// --------------------------------------------------------
// File Upload Constraints
// --------------------------------------------------------
define('UPLOAD_LIMIT', 20 * 1024 * 1024); // 20 Megabytes

define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt']);

define('ALLOWED_MIME_TYPES', [
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'ppt'  => ['application/vnd.ms-powerpoint'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'txt'  => ['text/plain']
]);

// --------------------------------------------------------
// Error Reporting (Security: Hide display errors in production)
// --------------------------------------------------------
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . 'logs/error.log');

// Ensure directories exist
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(BASE_PATH . 'logs/')) {
    @mkdir(BASE_PATH . 'logs/', 0755, true);
}
