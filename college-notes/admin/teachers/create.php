<?php
/**
 * College Notes Management System
 * Admin - Create Teacher
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $name        = sanitize($_POST['name'] ?? '');
    $employeeId  = sanitize($_POST['employee_id'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $department  = sanitize($_POST['department'] ?? '');
    $designation = sanitize($_POST['designation'] ?? '');
    $status      = sanitize($_POST['status'] ?? 'active');
    $password    = $_POST['password'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF mismatch).';
    } elseif (empty($name) || empty($employeeId) || empty($email) || empty($password)) {
        $error = 'Please fill in all mandatory fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check unique email
            $chk = $db->prepare("SELECT id FROM users WHERE email = :email");
            $chk->execute([':email' => $email]);
            if ($chk->fetch()) {
                $error = 'A user with this email already exists.';
            } else {
                // Check unique employee_id
                $chkId = $db->prepare("SELECT id FROM teachers WHERE employee_id = :eid");
                $chkId->execute([':eid' => $employeeId]);
                if ($chkId->fetch()) {
                    $error = 'This Employee ID is already in use.';
                } else {
                    $db->beginTransaction();
                    $pwHash = password_hash($password, PASSWORD_DEFAULT);
                    $uStmt = $db->prepare("
                        INSERT INTO users (name, email, password, role, status, created_at)
                        VALUES (:name, :email, :pass, 'teacher', :status, NOW())
                    ");
                    $uStmt->execute([
                        ':name'   => $name,
                        ':email'  => $email,
                        ':pass'   => $pwHash,
                        ':status' => $status
                    ]);
                    $newUserId = (int)$db->lastInsertId();

                    $tStmt = $db->prepare("
                        INSERT INTO teachers (user_id, employee_id, department, designation, phone, created_at)
                        VALUES (:uid, :eid, :dept, :desig, :phone, NOW())
                    ");
                    $tStmt->execute([
                        ':uid'   => $newUserId,
                        ':eid'   => $employeeId,
                        ':dept'  => $department,
                        ':desig' => $designation,
                        ':phone' => $phone
                    ]);
                    $db->commit();

                    redirect(BASE_URL . 'admin/teachers/index.php', 'Faculty member ' . $name . ' created successfully.', 'success');
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Add New Teacher";
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Add New Faculty Member</h1>
                <p class="page-subtitle">Register verified faculty member and assign department role</p>
            </div>
            <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Faculty List
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-9">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-user-plus text-success me-2"></i> Teacher Account Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>admin/teachers/create.php" method="POST">
                            <?= csrfField() ?>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Full Name *</label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Arthur Hayes" value="<?= e($_POST['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Employee ID *</label>
                                    <input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP-CS-501" value="<?= e($_POST['employee_id'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Faculty Email *</label>
                                    <input type="email" name="email" class="form-control" placeholder="faculty@collegenotes.edu" value="<?= e($_POST['email'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Contact Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="+1 555-0182" value="<?= e($_POST['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="">Select Department...</option>
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Electronics & Communication">Electronics & Communication</option>
                                        <option value="Information Technology">Information Technology</option>
                                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                                        <option value="Civil Engineering">Civil Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Designation *</label>
                                    <select name="designation" class="form-select" required>
                                        <option value="Professor">Professor</option>
                                        <option value="Associate Professor">Associate Professor</option>
                                        <option value="Assistant Professor">Assistant Professor</option>
                                        <option value="Lecturer">Lecturer</option>
                                        <option value="Head of Department (HOD)">Head of Department (HOD)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Account Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="blocked">Blocked</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Initial Password *</label>
                                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success fw-bold px-4">
                                    <i class="fas fa-save me-1"></i> Create Teacher Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
