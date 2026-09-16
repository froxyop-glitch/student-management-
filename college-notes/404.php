<?php
/**
 * College Notes Management System
 * Unified Error Page (404, 403, 500)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$code = http_response_code();
if ($code !== 403 && $code !== 500) {
    $code = 404;
    http_response_code(404);
}

$errorTitles = [
    403 => '403 - Access Forbidden',
    404 => '404 - Page Not Found',
    500 => '500 - Internal Service Issue'
];

$errorMessages = [
    403 => 'You do not have administrative or authorized permissions to view this resource.',
    404 => 'The resource, note, or directory you requested could not be located on the server.',
    500 => 'The portal encountered an unexpected issue. Please verify database connectivity or contact system administration.'
];

$pageTitle = $errorTitles[$code];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5 my-auto text-center">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4 bg-white">
                <div class="mb-4">
                    <span class="display-1 fw-extrabold <?= $code === 403 ? 'text-danger' : ($code === 500 ? 'text-warning' : 'text-primary') ?>">
                        <?= $code ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-3"><?= e($errorTitles[$code]) ?></h3>
                <p class="text-muted mb-4">
                    <?= e($errorMessages[$code]) ?>
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary px-4">
                        <i class="fas fa-home me-2"></i> Return Home
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-arrow-left me-2"></i> Go Back
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>user/login.php" class="btn btn-outline-secondary px-4">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
