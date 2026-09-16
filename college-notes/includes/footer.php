<?php
/**
 * Reusable Footer Layout
 * Variables available: $customJs (array)
 */
require_once __DIR__ . '/config.php';
$user = currentUser();
?>
    <?php if (!$user): ?>
    <!-- Public Footer -->
    <footer class="public-footer bg-dark text-white pt-5 pb-4 mt-auto">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="brand-icon-box bg-primary text-white">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <span class="fs-5 fw-bold text-white"><?= e(APP_NAME) ?></span>
                    </div>
                    <p class="text-white-50 small">
                        A centralized, secure academic resource repository designed for higher education institutions. Empowering students and faculty with seamless note exchange.
                    </p>
                    <div class="d-flex gap-3 text-white-50 fs-5">
                        <a href="#" class="text-white-50 hover-light"><i class="fab fa-github"></i></a>
                        <a href="#" class="text-white-50 hover-light"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-white-50 hover-light"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-uppercase fw-bold mb-3 text-warning">Quick Links</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="<?= BASE_URL ?>" class="text-white-50 text-decoration-none hover-light">Home</a></li>
                        <li><a href="<?= BASE_URL ?>user/notes.php" class="text-white-50 text-decoration-none hover-light">Explore Notes</a></li>
                        <li><a href="<?= BASE_URL ?>user/login.php" class="text-white-50 text-decoration-none hover-light">Student Login</a></li>
                        <li><a href="<?= BASE_URL ?>admin/login.php" class="text-white-50 text-decoration-none hover-light">Faculty & Admin</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-uppercase fw-bold mb-3 text-warning">Security Standards</h6>
                    <ul class="list-unstyled small text-white-50 d-flex flex-column gap-2">
                        <li><i class="fas fa-shield-alt text-success me-2"></i> Argon2/Bcrypt Password Hashing</li>
                        <li><i class="fas fa-lock text-success me-2"></i> CSRF & SQL Prepared Statements</li>
                        <li><i class="fas fa-file-shield text-success me-2"></i> File MIME Type Verification</li>
                        <li><i class="fas fa-user-shield text-success me-2"></i> Role-Based Access Control</li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-uppercase fw-bold mb-3 text-warning">Academic Support</h6>
                    <p class="text-white-50 small mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i> University Campus, Academic Block B</p>
                    <p class="text-white-50 small mb-2"><i class="fas fa-envelope me-2 text-primary"></i> support@collegenotes.edu</p>
                    <p class="text-white-50 small"><i class="fas fa-phone-alt me-2 text-primary"></i> +1 (800) 555-NOTE</p>
                </div>
            </div>

            <hr class="my-4 border-secondary">

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small text-white-50">
                <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
                <span class="mt-2 mt-sm-0">Built with Core PHP 8+, MySQL & Bootstrap 5</span>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap 5.3.3 JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Main Global JS -->
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>

    <?php if ($user): ?>
        <script src="<?= BASE_URL ?>assets/js/dashboard.js"></script>
    <?php endif; ?>

    <?php if (isset($customJs) && is_array($customJs)): ?>
        <?php foreach ($customJs as $js): ?>
            <script src="<?= BASE_URL ?>assets/js/<?= e($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
