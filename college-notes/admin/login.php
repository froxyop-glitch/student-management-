<?php
/**
 * College Notes Management System
 * Dedicated Administrator Login Portal
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in as admin, redirect to dashboard
if (isLoggedIn()) {
    if (currentRole() === 'admin') {
        redirect(BASE_URL . 'admin/dashboard.php');
    } else {
        redirect(BASE_URL . 'index.php', 'You do not have administrative credentials.', 'warning');
    }
}

$error = '';
$clientIp = getClientIp();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif (isRateLimited($email, $clientIp)) {
        $error = 'Maximum login attempts reached. Administrative access from this IP is locked for 15 minutes.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter your administrator email and master password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = :email AND role = 'admin' LIMIT 1");
            $stmt->execute([':email' => $email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if ($admin['status'] !== 'active') {
                    $error = 'Administrator account disabled by system policy.';
                    recordLoginAttempt($email, $clientIp, 'failed');
                } else {
                    recordLoginAttempt($email, $clientIp, 'success');
                    loginUser($admin);
                    redirect(BASE_URL . 'admin/dashboard.php', 'Welcome to Central Administrative Control, ' . $admin['name'] . '.', 'success');
                }
            } else {
                recordLoginAttempt($email, $clientIp, 'failed');
                $error = 'Unauthorized administrative credentials.';
            }
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error = 'Authentication service unavailable.';
        }
    }
}

$pageTitle = "Administrator Authentication";
$customCss = ['auth.css'];
$customJs = ['auth.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header admin-theme">
            <span class="badge-tag bg-warning text-dark fw-bold"><i class="fas fa-shield-alt me-1"></i> Admin Zone</span>
            <div class="brand-icon-box mx-auto mb-2 bg-warning text-dark" style="width: 48px; height: 48px; font-size: 1.4rem;">
                <i class="fas fa-user-shield"></i>
            </div>
            <h4 class="fw-bold mb-1">Administrative Gateway</h4>
            <p class="text-white-50 small mb-0">Restricted access for designated university IT officers</p>
        </div>

        <div class="auth-body">
            <?php displayFlashMessage(); ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div><?= e($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning py-2 small mb-4">
                <i class="fas fa-info-circle me-1"></i> All administrative login attempts and IP addresses are audited.
            </div>

            <form action="<?= BASE_URL ?>admin/login.php" method="POST" autocomplete="off">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Administrator Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-lock"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="admin@collegenotes.edu" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Master Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                        <input type="password" name="password" id="adminPassword" class="form-control" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary toggle-password-btn" type="button" data-target="adminPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger w-100 py-2 fw-bold shadow-sm">
                    <i class="fas fa-shield-alt me-2"></i> Authorize Administration
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <a href="<?= BASE_URL ?>user/login.php" class="small text-muted text-decoration-none">
                    &larr; Return to Student & Faculty Portal
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
