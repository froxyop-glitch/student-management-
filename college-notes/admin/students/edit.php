<?php
/**
 * College Notes Management System
 * Admin - Edit Student
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$studentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$studentId) {
    redirect(BASE_URL . 'admin/students/index.php', 'Invalid student specified.', 'danger');
}

$stmt = $db->prepare("
    SELECT s.*, u.id as user_id, u.name, u.email, u.status
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $studentId]);
$student = $stmt->fetch();

if (!$student) {
    redirect(BASE_URL . 'admin/students/index.php', 'Student record not found.', 'danger');
}

$error = '';
$semesters = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken  = $_POST['csrf_token'] ?? '';
    $name       = sanitize($_POST['name'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $semester   = sanitize($_POST['semester'] ?? '');
    $batch      = sanitize($_POST['batch'] ?? '');
    $status     = sanitize($_POST['status'] ?? 'active');
    $newPass    = $_POST['new_password'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF mismatch).';
    } elseif (empty($name) || empty($department) || empty($semester) || empty($batch)) {
        $error = 'Please fill in all mandatory fields.';
    } else {
        try {
            $db->beginTransaction();
            $uSql = "UPDATE users SET name = :name, status = :status WHERE id = :uid";
            $uParams = [':name' => $name, ':status' => $status, ':uid' => $student['user_id']];

            if (!empty($newPass)) {
                if (strlen($newPass) < 6) {
                    throw new Exception('New password must be at least 6 characters.');
                }
                $uSql = "UPDATE users SET name = :name, status = :status, password = :pwd WHERE id = :uid";
                $uParams[':pwd'] = password_hash($newPass, PASSWORD_DEFAULT);
            }

            $uStmt = $db->prepare($uSql);
            $uStmt->execute($uParams);

            $sStmt = $db->prepare("
                UPDATE students SET
                    department = :dept,
                    semester = :sem,
                    batch = :batch,
                    phone = :phone
                WHERE id = :id
            ");
            $sStmt->execute([
                ':dept'  => $department,
                ':sem'   => $semester,
                ':batch' => $batch,
                ':phone' => $phone,
                ':id'    => $studentId
            ]);

            $db->commit();
            redirect(BASE_URL . 'admin/students/index.php', 'Student details updated successfully.', 'success');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

$pageTitle = "Edit Student: " . $student['name'];
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Edit Student Profile</h1>
                <p class="page-subtitle">Update student curriculum data, enrollment status, or password</p>
            </div>
            <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-outline-secondary">
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
                        <h5 class="fw-bold mb-0"><i class="fas fa-user-edit text-primary me-2"></i> Student Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>admin/students/edit.php?id=<?= $studentId ?>" method="POST">
                            <?= csrfField() ?>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student Name *</label>
                                    <input type="text" name="name" class="form-control" value="<?= e($student['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student ID</label>
                                    <input type="text" class="form-control bg-light" value="<?= e($student['student_id']) ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Academic Email</label>
                                    <input type="email" class="form-control bg-light" value="<?= e($student['email']) ?>" readonly disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Contact Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($student['phone'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="Computer Science" <?= $student['department'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                        <option value="Electronics & Communication" <?= $student['department'] === 'Electronics & Communication' ? 'selected' : '' ?>>Electronics & Communication</option>
                                        <option value="Information Technology" <?= $student['department'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                        <option value="Mechanical Engineering" <?= $student['department'] === 'Mechanical Engineering' ? 'selected' : '' ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?= $student['department'] === 'Civil Engineering' ? 'selected' : '' ?>>Civil Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Semester *</label>
                                    <select name="semester" class="form-select" required>
                                        <?php foreach ($semesters as $s): ?>
                                            <option value="<?= e($s['semester_name']) ?>" <?= $student['semester'] === $s['semester_name'] ? 'selected' : '' ?>><?= e($s['semester_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Batch *</label>
                                    <input type="text" name="batch" class="form-control" value="<?= e($student['batch']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Account Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $student['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $student['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="blocked" <?= $student['status'] === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-warning">Reset Password (Optional)</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="Leave empty to retain existing password">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="fas fa-save me-1"></i> Update Student
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
