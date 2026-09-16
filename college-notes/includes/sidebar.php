<?php
/**
 * College Notes Management System
 * Role-Based Dynamic Sidebar Navigation
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$user = currentUser();
$role = currentRole();
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<aside class="sidebar-wrapper" id="sidebarWrapper">
    <div class="sidebar-inner d-flex flex-column">
        <!-- Sidebar Brand / Role Header -->
        <div class="sidebar-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="role-indicator-dot role-<?= e($role) ?>"></span>
                <span class="text-uppercase fw-bold fs-7 tracking-wide text-muted"><?= e($role) ?> Portal</span>
            </div>
            <button class="btn btn-sm btn-link text-muted d-lg-none" id="sidebarCloseBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navigation Links -->
        <ul class="nav flex-column sidebar-nav p-2 flex-grow-1">
            <?php if ($role === 'student'): ?>
                <!-- Student Menu -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'dashboard.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>user/dashboard.php">
                        <i class="fas fa-th-large nav-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($currentScript, 'notes.php') !== false || strpos($currentScript, 'note-view.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>user/notes.php">
                        <i class="fas fa-book-open nav-icon"></i>
                        <span>Browse Notes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'subjects.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>user/subjects.php">
                        <i class="fas fa-layer-group nav-icon"></i>
                        <span>Subjects</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'downloads.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>user/downloads.php">
                        <i class="fas fa-download nav-icon"></i>
                        <span>My Downloads</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'profile.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>user/profile.php">
                        <i class="fas fa-user-circle nav-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>

            <?php elseif ($role === 'teacher'): ?>
                <!-- Teacher Menu -->
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'dashboard.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>teacher/dashboard.php">
                        <i class="fas fa-chart-line nav-icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($currentScript, 'notes.php') !== false && strpos($currentScript, 'create-note.php') === false && strpos($currentScript, 'edit-note.php') === false) ? 'active' : '' ?>" href="<?= BASE_URL ?>teacher/notes.php">
                        <i class="fas fa-folder-open nav-icon"></i>
                        <span>My Notes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'create-note.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>teacher/create-note.php">
                        <i class="fas fa-cloud-upload-alt nav-icon text-success"></i>
                        <span>Upload Note</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'profile.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>teacher/profile.php">
                        <i class="fas fa-id-card nav-icon"></i>
                        <span>Profile</span>
                    </a>
                </li>

            <?php elseif ($role === 'admin'): ?>
                <!-- Admin Menu -->
                <li class="nav-item">
                    <a class="nav-link <?= (strpos($currentScript, 'admin/dashboard.php') !== false) ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/dashboard.php">
                        <i class="fas fa-tachometer-alt nav-icon"></i>
                        <span>Admin Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'teachers') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/teachers/index.php">
                        <i class="fas fa-chalkboard-teacher nav-icon"></i>
                        <span>Teachers CRUD</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'students') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/students/index.php">
                        <i class="fas fa-user-graduate nav-icon"></i>
                        <span>Students CRUD</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'admin/notes') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/notes/index.php">
                        <i class="fas fa-file-contract nav-icon"></i>
                        <span>Notes Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'admin/subjects') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/subjects/index.php">
                        <i class="fas fa-book-reader nav-icon"></i>
                        <span>Subjects Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentScript, 'backup.php') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/backup.php">
                        <i class="fas fa-database nav-icon text-warning"></i>
                        <span>Database Backup</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-item mt-4 pt-3 border-top">
                <a class="nav-link text-danger" href="<?= BASE_URL ?>user/logout.php">
                    <i class="fas fa-power-off nav-icon text-danger"></i>
                    <span>Sign Out</span>
                </a>
            </li>
        </ul>

        <!-- Bottom Status -->
        <div class="sidebar-footer p-3 border-top bg-light-subtle small text-muted">
            <div class="d-flex align-items-center justify-content-between">
                <span><i class="fas fa-shield-alt text-success me-1"></i> SSL/TLS Secure</span>
                <span>v<?= APP_VERSION ?></span>
            </div>
        </div>
    </div>
</aside>
