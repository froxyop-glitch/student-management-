<?php
/**
 * College Notes Management System
 * Authentication & Session Management Module
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// --------------------------------------------------------
// 1. Secure Session Initialization
// --------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    session_set_cookie_params([
        'lifetime' => 0,               // Session cookie until browser closed
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,        // Send only over HTTPS if available
        'httponly' => true,             // Mitigate XSS cookie theft
        'samesite' => 'Lax'             // Mitigate CSRF
    ]);

    session_start();
}

// --------------------------------------------------------
// 2. Inactivity Timeout Enforcement
// --------------------------------------------------------
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Log activity timeout
        $timedOutRole = $_SESSION['user_role'] ?? 'user';
        logoutUser();

        // Redirect with message
        $redirectUrl = ($timedOutRole === 'admin') ? BASE_URL . 'admin/login.php' : BASE_URL . 'user/login.php';
        redirect($redirectUrl, 'Your session has expired due to 30 minutes of inactivity. Please log in again.', 'warning');
    }
    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
}

// --------------------------------------------------------
// 3. User State Inspection
// --------------------------------------------------------

/**
 * Check if current user is logged in
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Get current logged in user role ('admin', 'teacher', 'student')
 * 
 * @return string|null
 */
function currentRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get full session payload of current user
 * 
 * @return array|null
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'            => $_SESSION['user_id'],
        'name'          => $_SESSION['user_name'] ?? '',
        'email'         => $_SESSION['user_email'] ?? '',
        'role'          => $_SESSION['user_role'] ?? '',
        'profile_image' => $_SESSION['profile_image'] ?? null,
        'profile_id'    => $_SESSION['profile_id'] ?? null // teacher_id or student_id PK
    ];
}

// --------------------------------------------------------
// 4. Authorization Guards
// --------------------------------------------------------

/**
 * Require active authentication
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $loginUrl = BASE_URL . 'user/login.php';
        redirect($loginUrl, 'Please log in to access this page.', 'warning');
    }
}

/**
 * Require a specific role or set of roles
 * 
 * @param string|array $roles Expected role(s)
 */
function requireRole(string|array $roles): void {
    requireLogin();

    $allowed = is_array($roles) ? $roles : [$roles];
    $currentRole = currentRole();

    if (!in_array($currentRole, $allowed, true)) {
        // 403 Forbidden - Role violation
        http_response_code(403);
        setFlashMessage('danger', 'Access denied. You do not have permission to view this resource.');
        
        // Redirect to their appropriate dashboard
        $redirect = match($currentRole) {
            'admin'   => BASE_URL . 'admin/dashboard.php',
            'teacher' => BASE_URL . 'teacher/dashboard.php',
            'student' => BASE_URL . 'user/dashboard.php',
            default   => BASE_URL . 'index.php'
        };
        redirect($redirect, 'Access denied. You do not have permission to view this resource.', 'danger');
    }
}

// --------------------------------------------------------
// 5. Login & Logout Workflows
// --------------------------------------------------------

/**
 * Login user and regenerate session ID to prevent session fixation
 * 
 * @param array $user DB row from users table
 * @return void
 */
function loginUser(array $user): void {
    // Regenerate session ID immediately on privilege change
    session_regenerate_id(true);

    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? null;
    $_SESSION['last_activity'] = time();

    // Fetch related profile ID (teachers.id or students.id)
    $db = getDB();
    if ($user['role'] === 'teacher') {
        $stmt = $db->prepare("SELECT id, department FROM teachers WHERE user_id = :uid");
        $stmt->execute([':uid' => $user['id']]);
        $teacher = $stmt->fetch();
        $_SESSION['profile_id'] = $teacher ? $teacher['id'] : null;
        $_SESSION['department'] = $teacher ? $teacher['department'] : null;
    } elseif ($user['role'] === 'student') {
        $stmt = $db->prepare("SELECT id, student_id, semester, department FROM students WHERE user_id = :uid");
        $stmt->execute([':uid' => $user['id']]);
        $student = $stmt->fetch();
        $_SESSION['profile_id'] = $student ? $student['id'] : null;
        $_SESSION['student_code'] = $student ? $student['student_id'] : null;
        $_SESSION['semester']   = $student ? $student['semester'] : null;
        $_SESSION['department'] = $student ? $student['department'] : null;
    }

    // Reset failed login logs for this user/IP
    resetLoginAttempts($user['email'], getClientIp());
}

/**
 * Securely terminate user session and clear cookies
 * 
 * @return void
 */
function logoutUser(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }
}
