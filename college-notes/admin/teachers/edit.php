<?php
/**
 * College Notes Management System
 * Admin - Edit Teacher
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$teacherId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$teacherId) {
    redirect(BASE_URL . 'admin/teachers/index.php', 'Invalid teacher specified.', 'danger');
}

$stmt = $db->prepare("
    SELECT t.*, u.id as user_id, u.name, u.email, u.status
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $teacherId]);
$teacher = $stmt->fetch();

if (!$teacher) {
    redirect(BASE_URL . 'admin/teachers/index.php', 'Faculty member not found.', 'danger');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $name        = sanitize($_POST['name'] ?? '');
    $phone       = sanitize($_POST['phone'] ?? '');
    $department  = sanitize($_POST['department'] ?? '');
    $designation = sanitize($_POST['designation'] ?? '');
    $status      = sanitize($_POST['status'] ?? 'active');
    $newPass     = $_POST['new_password'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF mismatch).';
    } elseif (empty($name) || empty($department) || empty($designation)) {
        $error = 'Please fill in all mandatory fields.';
    } else {
        try {
            $db->beginTransaction();
            $uSql = "UPDATE users SET name = :name, status = :status WHERE id = :uid";
            $uParams = [':name' => $name, ':status' => $status, ':uid' => $teacher['user_id']];
            
            if (!empty($newPass)) {
                if (strlen($newPass) < 6) {
                    throw new Exception('New password must be at least 6 characters.');
                }
                $uSql = "UPDATE users SET name = :name, status = :status, password = :pwd WHERE id = :uid";
                $uParams[':pwd'] = password_hash($newPass, PASSWORD_DEFAULT);
            }

            $uStmt = $db->prepare($uSql);
            $uStmt->execute($uParams);

            $tStmt = $db->prepare("
                UPDATE teachers SET 
                    department = :dept, 
                    designation = :desig, 
                    phone = :phone 
                WHERE id = :id
            ");
            $tStmt->execute([
                ':dept'  => $department,
                ':desig' => $designation,
                ':phone' => $phone,
                ':id'    => $teacherId
            ]);

            $db->commit();
            redirect(BASE_URL . 'admin/teachers/index.php', 'Faculty record updated successfully.', 'success');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = "Edit Teacher: " . $teacher['name'];
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Edit Faculty Member</h1>
                <p class="page-subtitle">Update department role, contact details, or reset credentials</p>
            </div>
            <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Cancel & Return
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
                        <h5 class="fw-bold mb-0"><i class="fas fa-user-edit text-primary me-2"></i> Faculty Profile</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>admin/teachers/edit.php?id=<?= $teacherId ?>" method="POST">
                            <?= csrfField() ?>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Faculty Name *</label>
                                    <input type="text" name="name" class="form-control" value="<?= e($teacher['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Employee ID</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($teacher['employee_id']) ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Email Address</label>
                                    <input type="email" class="form-control bg-light" value="<?= e($teacher['email']) ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Contact Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="Computer Science" <?= $teacher['department'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                        <option value="Electronics & Communication" <?= $teacher['department'] === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Communication</option>
                                        <option value="Information Technology" <?= $teacher['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                        <option value="Mechanical Engineering" <?= $teacher['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?= $teacher['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Designation *</label>
                                    <select name="designation" class="form-select" required>
                                        <option value="Professor" <?= $teacher['designation'] === 'Professor' ? 'selected' : '' ?>>Professor</option>
                                        <option value="Associate Professor" <?= $teacher['designation'] === 'Associate Professor' ? 'selected' : '' ?>>Associate Professor</option>
                                        <option value="Assistant Professor" <?= $teacher['designation'] === 'Assistant Professor' ? 'selected' : '' ?>>Assistant Professor</option>
                                        <option value="Lecturer" <?= $teacher['designation'] === 'Lecturer' ? 'selected' : '' ?>>Lecturer</option>
                                        <option value="Head of Department (HOD)" <?= $teacher['designation'] === 'Head of Department (HOD)' ? 'selected' : '' ?>>Head of Department (HOD)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Account Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $teacher['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $teacher['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="blocked" <?= $teacher['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold text-warning">Reset Password (Optional)</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="Leave empty to retain existing password">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="fas fa-save me-1"></i> Update Faculty Member
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
