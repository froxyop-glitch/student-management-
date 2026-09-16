<?php
/**
 * College Notes Management System
 * Shared Top Navigation Bar
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user = currentUser();
$role = currentRole();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-navy sticky-top shadow-sm main-navbar">
    <div class="container-fluid px-lg-4">
        <?php if ($user): ?>
            <!-- Toggle Sidebar for Mobile -->
            <button class="btn btn-outline-light d-lg-none me-2 btn-sm" type="button" id="sidebarToggle" aria-label="Toggle Navigation">
                <i class="fas fa-bars"></i>
            </button>
        <?php endif; ?>

        <!-- Brand Logo & Name -->
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>">
            <div class="brand-icon-box">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="brand-text"><?= e(APP_SHORT_NAME) ?> <small class="brand-badge d-none d-sm-inline">Portal</small></span>
        </a>

        <!-- Mobile Brand Collapse Button for Public View -->
        <?php if (!$user): ?>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavCollapse" aria-controls="publicNavCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        <?php endif; ?>

        <?php if ($user): ?>
            <!-- Dashboard User Navigation (Right Side) -->
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="d-none d-md-flex flex-column text-end">
                    <span class="fw-semibold text-white user-name-label"><?= e($user['name']) ?></span>
                    <span class="badge bg-light text-dark text-uppercase role-badge align-self-end"><?= e($user['role']) ?></span>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-link text-white p-0 dropdown-toggle dropdown-toggle-split text-decoration-none d-flex align-items-center gap-2" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userMenuButton">
                        <li class="dropdown-header text-muted">
                            <div class="fw-bold text-dark"><?= e($user['name']) ?></div>
                            <small><?= e($user['email']) ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($role === 'student'): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>user/profile.php"><i class="fas fa-id-badge me-2 text-primary"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>user/notes.php"><i class="fas fa-book-open me-2 text-info"></i>Browse Notes</a></li>
                        <?php elseif ($role === 'teacher'): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>teacher/profile.php"><i class="fas fa-id-badge me-2 text-primary"></i>Teacher Profile</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>teacher/create-note.php"><i class="fas fa-cloud-upload-alt me-2 text-success"></i>Upload Notes</a></li>
                        <?php elseif ($role === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-cogs me-2 text-primary"></i>Admin Panel</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/backup.php"><i class="fas fa-database me-2 text-warning"></i>Database Backup</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>user/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <!-- Public Landing Navigation Links -->
            <div class="collapse navbar-collapse" id="publicNavCollapse">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>user/notes.php"><i class="fas fa-book me-1"></i> Browse Notes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>#about"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>#features"><i class="fas fa-star me-1"></i> Features</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light btn-sm px-3 rounded-pill" href="<?= BASE_URL ?>user/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm px-3 rounded-pill fw-semibold text-dark" href="<?= BASE_URL ?>user/register.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>
