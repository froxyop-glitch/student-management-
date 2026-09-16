<?php
/**
 * College Notes Management System
 * Faculty Profile Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['teacher', 'admin']);

$user = currentUser();
$db = getDB();
$error = '';
$success = '';

// Fetch teacher profile
$stmt = $db->prepare("SELECT * FROM teachers WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$teacher = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action    = $_POST['action'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif ($action === 'update_profile') {
        $name        = sanitize($_POST['name'] ?? '');
        $phone       = sanitize($_POST['phone'] ?? '');
        $designation = sanitize($_POST['designation'] ?? '');

        if (empty($name)) {
            $error = 'Name cannot be blank.';
        } else {
            try {
                $db->beginTransaction();
                $uStmt = $db->prepare("UPDATE users SET name = :name WHERE id = :id");
                $uStmt->execute([':name' => $name, ':id' => $user['id']]);

                if ($teacher) {
                    $tStmt = $db->prepare("UPDATE teachers SET phone = :phone, designation = :desig WHERE user_id = :uid");
                    $tStmt->execute([':phone' => $phone, ':desig' => $designation, ':uid' => $user['id']]);
                }

                $db->commit();
                $_SESSION['user_name'] = $name;
                $success = 'Faculty profile details updated successfully.';
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
            $error = 'Please provide current and new passwords.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'Passwords do not match.';
        } else {
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $user['id']]);
            $hash = $stmt->fetchColumn();

            if (!password_verify($currentPass, $hash)) {
                $error = 'Incorrect current password.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $uStmt = $db->prepare("UPDATE users SET password = :p WHERE id = :id");
                $uStmt->execute([':p' => $newHash, ':id' => $user['id']]);
                $success = 'Password changed successfully.';
            }
        }
    }
}

// Refresh teacher data
$stmt = $db->prepare("SELECT * FROM teachers WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$teacher = $stmt->fetch();

$pageTitle = "Faculty Profile";
$customCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Faculty Profile</h1>
                <p class="page-subtitle">Manage your academic credentials and contact information</p>
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
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-id-card text-success me-2"></i> Faculty Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>teacher/profile.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Faculty Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Faculty Email (Login ID)</label>
                                <input type="email" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly disabled>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Employee ID</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($teacher['employee_id'] ?? '') ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Department</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($teacher['department'] ?? '') ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Designation</label>
                                    <input type="text" name="designation" class="form-control" value="<?= e($teacher['designation'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Contact Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success fw-bold">
                                <i class="fas fa-save me-1"></i> Update Faculty Details
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-lock text-warning me-2"></i> Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>teacher/profile.php" method="POST">
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
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
