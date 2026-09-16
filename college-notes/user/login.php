<?php
/**
 * College Notes Management System
 * User (Student / Teacher) Login Page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    $role = currentRole();
    redirect(match($role) {
        'admin' => BASE_URL . 'admin/dashboard.php',
        'teacher' => BASE_URL . 'teacher/dashboard.php',
        default => BASE_URL . 'user/dashboard.php'
    });
}

$error = '';
$clientIp = getClientIp();

// Process POST Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // 1. CSRF Verification
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (Invalid CSRF Token). Please refresh and try again.';
    }
    // 2. Rate Limiting / Brute-force Defense
    elseif (isRateLimited($email, $clientIp)) {
        $error = 'Too many failed login attempts. This IP/account is locked out for 15 minutes.';
    }
    // 3. Validation
    elseif (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid college email address.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['status'] !== 'active') {
                    $error = 'Your account status is currently ' . e($user['status']) . '. Please contact administration.';
                    recordLoginAttempt($email, $clientIp, 'failed');
                } else {
                    // Record success & authenticate
                    recordLoginAttempt($email, $clientIp, 'success');
                    loginUser($user);

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        redirect(BASE_URL . 'admin/dashboard.php', 'Welcome back, Administrator!', 'success');
                    } elseif ($user['role'] === 'teacher') {
                        redirect(BASE_URL . 'teacher/dashboard.php', 'Welcome back, ' . $user['name'] . '!', 'success');
                    } else {
                        redirect(BASE_URL . 'user/dashboard.php', 'Welcome back, ' . $user['name'] . '!', 'success');
                    }
                }
            } else {
                // Generic error to prevent account enumeration
                recordLoginAttempt($email, $clientIp, 'failed');
                $error = 'Invalid email address or password combination.';
            }
        } catch (PDOException $e) {
            error_log("Login Query Exception: " . $e->getMessage());
            $error = 'A database error occurred. Please try again later.';
        }
    }
}

$pageTitle = "Login to Portal";
$customCss = ['auth.css'];
$customJs = ['auth.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span class="badge-tag"><i class="fas fa-lock me-1"></i> SSL Protected</span>
            <div class="brand-icon-box mx-auto mb-2" style="width: 44px; height: 44px;">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h4 class="fw-bold mb-1">Academic Portal Login</h4>
            <p class="text-white-50 small mb-0">Sign in with your registered college credentials</p>
        </div>

        <div class="auth-body">
            <?php displayFlashMessage(); ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div><?= e($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>user/login.php" method="POST" autocomplete="off">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">College Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="name@collegenotes.edu" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-semibold text-secondary mb-0">Account Password</label>
                        <a href="javascript:void(0)" onclick="alert('Please contact the campus system administrator or IT helpdesk to reset your password.')" class="small text-decoration-none">Forgot password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="loginPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In to Account
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <p class="small text-muted mb-2">New student or faculty member?</p>
                <a href="<?= BASE_URL ?>user/register.php" class="btn btn-outline-secondary btn-sm w-100 fw-semibold">
                    <i class="fas fa-user-plus me-1"></i> Register New Account
                </a>
            </div>

            <div class="mt-3 text-center">
                <a href="<?= BASE_URL ?>admin/login.php" class="small text-muted text-decoration-none">
                    <i class="fas fa-shield-alt me-1"></i> Administrative Portal Access &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
