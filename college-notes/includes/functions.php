<?php
/**
 * College Notes Management System
 * Core Utility & Security Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// --------------------------------------------------------
// 1. XSS Escaping & Input Sanitization
// --------------------------------------------------------

/**
 * Escape HTML output to prevent XSS
 * 
 * @param mixed $string
 * @return string
 */
function e($string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize basic string inputs
 * 
 * @param mixed $data
 * @return string
 */
function sanitize($data): string {
    if (is_array($data)) {
        return '';
    }
    return trim(strip_tags((string)$data));
}

// --------------------------------------------------------
// 2. CSRF Protection
// --------------------------------------------------------

/**
 * Generate CSRF token and store in session
 * 
 * @return string
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token
 * 
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF hidden input field
 * 
 * @return string
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

// --------------------------------------------------------
// 3. Flash Messaging & Redirects
// --------------------------------------------------------

/**
 * Set a flash message for next request
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * Render flash alert banner if exists
 */
function displayFlashMessage(): void {
    $flash = getFlashMessage();
    if ($flash) {
        $alertType = $flash['type'] === 'error' ? 'danger' : e($flash['type']);
        $icon = match($flash['type']) {
            'success' => 'fa-check-circle',
            'danger', 'error' => 'fa-exclamation-triangle',
            'warning' => 'fa-exclamation-circle',
            default => 'fa-info-circle'
        };
        echo '<div class="alert alert-' . $alertType . ' alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                <i class="fas ' . $icon . ' me-2 fa-lg"></i>
                <div class="flex-grow-1">' . e($flash['message']) . '</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

/**
 * Safe HTTP redirect
 * 
 * @param string $url
 * @param string|null $message
 * @param string $type
 */
function redirect(string $url, ?string $message = null, string $type = 'info'): void {
    if ($message !== null) {
        setFlashMessage($type, $message);
    }
    header("Location: " . $url);
    exit;
}

// --------------------------------------------------------
// 4. Rate Limiting & Brute Force Defense
// --------------------------------------------------------

/**
 * Get client IP address safely
 * 
 * @return string
 */
function getClientIp(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '127.0.0.1';
}

/**
 * Check if an IP/Email is currently rate-limited
 * 
 * @param string $email
 * @param string $ip
 * @return bool True if locked out, False if allowed
 */
function isRateLimited(string $email, string $ip): bool {
    try {
        $db = getDB();
        $since = date('Y-m-d H:i:s', time() - LOCKOUT_TIME);
        
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM login_logs 
            WHERE (email = :email OR ip_address = :ip) 
              AND status = 'failed' 
              AND attempted_at >= :since
        ");
        $stmt->execute([
            ':email' => $email,
            ':ip' => $ip,
            ':since' => $since
        ]);
        
        $failedCount = (int)$stmt->fetchColumn();
        return $failedCount >= MAX_LOGIN_ATTEMPTS;
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Record a login attempt
 * 
 * @param string $email
 * @param string $ip
 * @param string $status ('success' | 'failed')
 */
function recordLoginAttempt(string $email, string $ip, string $status): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO login_logs (email, ip_address, status, attempted_at)
            VALUES (:email, :ip, :status, NOW())
        ");
        $stmt->execute([
            ':email' => substr($email, 0, 150),
            ':ip' => $ip,
            ':status' => $status
        ]);
    } catch (PDOException $e) {
        error_log("Recording login attempt failed: " . $e->getMessage());
    }
}

/**
 * Reset failed attempts on successful login
 * 
 * @param string $email
 * @param string $ip
 */
function resetLoginAttempts(string $email, string $ip): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM login_logs 
            WHERE (email = :email OR ip_address = :ip)
        ");
        $stmt->execute([':email' => $email, ':ip' => $ip]);
    } catch (PDOException $e) {
        error_log("Resetting login attempts failed: " . $e->getMessage());
    }
}

// --------------------------------------------------------
// 5. File Upload Security & Validation
// --------------------------------------------------------

/**
 * Validate and process an uploaded note document
 * 
 * @param array $file $_FILES['note_file']
 * @return array Result array with status, errors, and stored file details
 */
function validateAndSaveNoteUpload(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload parameter.'];
    }

    // Check PHP upload error codes
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'error' => 'Please select a file to upload.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'error' => 'File exceeds maximum upload size of ' . (UPLOAD_LIMIT / (1024 * 1024)) . 'MB.'];
        default:
            return ['success' => false, 'error' => 'An unknown error occurred during file upload.'];
    }

    // Check size against application constant
    if ($file['size'] > UPLOAD_LIMIT) {
        return ['success' => false, 'error' => 'File size exceeds limit of ' . (UPLOAD_LIMIT / (1024 * 1024)) . 'MB.'];
    }

    // Extract and validate extension
    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, ALLOWED_EXTENSIONS, true)) {
        return ['success' => false, 'error' => 'File type .' . e($extension) . ' is not permitted. Allowed: PDF, DOC, DOCX, PPT, PPTX, TXT.'];
    }

    // Inspect real MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);

    $allowedMimesForExt = ALLOWED_MIME_TYPES[$extension] ?? [];
    if (!in_array($realMime, $allowedMimesForExt, true)) {
        // Fallback check for certain Office xml packages detected as octet-stream or generic zip
        $isOfficeDoc = in_array($extension, ['docx', 'pptx']) && ($realMime === 'application/zip' || strpos($realMime, 'application/vnd.openxmlformats') !== false);
        if (!$isOfficeDoc) {
            return ['success' => false, 'error' => 'File content does not match its declared extension (detected: ' . e($realMime) . ').'];
        }
    }

    // Generate randomized collision-resistant unique filename
    $uniqueName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
    $targetPath = UPLOAD_DIR . $uniqueName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save uploaded file to destination server.'];
    }

    // Return sanitized metadata
    return [
        'success'      => true,
        'stored_name'  => $uniqueName,
        'original_name'=> $originalName,
        'relative_path'=> 'assets/uploads/notes/' . $uniqueName,
        'file_type'    => $extension,
        'file_size'    => $file['size']
    ];
}

// --------------------------------------------------------
// 6. Formatting & Display Helpers
// --------------------------------------------------------

/**
 * Format raw bytes into human-readable string
 * 
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Return human readable time difference
 * 
 * @param string $datetime
 * @return string
 */
function timeAgo(string $datetime): string {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = round($difference / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = round($difference / 3600);
        return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = round($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Return FontAwesome icon and badge color for file extension
 * 
 * @param string $type
 * @return array [icon, color_class]
 */
function getFileTypeInfo(string $type): array {
    return match(strtolower($type)) {
        'pdf'  => ['icon' => 'fa-file-pdf', 'color' => 'text-danger', 'badge' => 'bg-danger'],
        'doc', 'docx' => ['icon' => 'fa-file-word', 'color' => 'text-primary', 'badge' => 'bg-primary'],
        'ppt', 'pptx' => ['icon' => 'fa-file-powerpoint', 'color' => 'text-warning', 'badge' => 'bg-warning text-dark'],
        'txt'  => ['icon' => 'fa-file-alt', 'color' => 'text-secondary', 'badge' => 'bg-secondary'],
        default => ['icon' => 'fa-file', 'color' => 'text-muted', 'badge' => 'bg-dark']
    };
}
