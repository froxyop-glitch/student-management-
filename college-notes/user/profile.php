<?php
/**
 * College Notes Management System
 * Student / User Profile Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = currentUser();
$db = getDB();
$error = '';
$success = '';

// Fetch student extra fields
$student = null;
if ($user['role'] === 'student') {
    $stmt = $db->prepare("SELECT * FROM students WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user['id']]);
    $student = $stmt->fetch();
}

// Handle Profile Update Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? 'update_profile';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($name)) {
            $error = 'Name cannot be blank.';
        } else {
            try {
                $db->beginTransaction();
                $uStmt = $db->prepare("UPDATE users SET name = :name WHERE id = :id");
                $uStmt->execute([':name' => $name, ':id' => $user['id']]);

                if ($user['role'] === 'student' && $student) {
                    $sStmt = $db->prepare("UPDATE students SET phone = :phone WHERE user_id = :uid");
                    $sStmt->execute([':phone' => $phone, ':uid' => $user['id']]);
                }

                $db->commit();
                $_SESSION['user_name'] = $name;
                $success = 'Your profile details have been successfully updated.';
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            $error = 'Please provide your current and new password.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'The new passwords do not match.';
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $user['id']]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($currentPass, $hash)) {
                $error = 'The current password you entered is incorrect.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $uStmt = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
                $uStmt->execute([':p' => $newHash, ':id' => $user['id']]);
                $success = 'Password successfully updated!';
            }
        }
    }
}

// Refresh student record
if ($user['role'] === 'student') {
    $stmt = $db->prepare("SELECT * FROM students WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user['id']]);
    $student = $stmt->fetch();
}

$pageTitle = "My Profile";
$customCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Profile Settings</h1>
                <p class="page-subtitle">Manage your account information and login credentials</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-user-edit text-primary me-2"></i> Personal Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>user/profile.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Email Address (Academic Identifier)</label>
                                <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly disabled>
                                <div class="form-text small">To change your primary email, contact academic administration.</div>
                            </div>

                            <?php if ($user['role'] === 'student' && $student): ?>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Student ID</label>
                                        <input type="text" class="form-control bg-light" value="<?= e($student['student_id']) ?>" readonly disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Contact Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?= e($student['phone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Department</label>
                                        <input type="text" class="form-control bg-light" value="<?= e($student['department']) ?>" readonly disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Current Semester</label>
                                        <input type="text" class="form-control bg-light" value="<?= e($student['semester']) ?>" readonly disabled>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-key text-warning me-2"></i> Update Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>user/profile.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-warning fw-bold text-dark w-100">
                                <i class="fas fa-lock me-1"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
