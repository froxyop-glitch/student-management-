<?php
/**
 * College Notes Management System
 * Admin - Enroll Student
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$error = '';
$semesters = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken  = $_POST['csrf_token'] ?? '';
    $name       = sanitize($_POST['name'] ?? '');
    $studentId  = sanitize($_POST['student_id'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $department = sanitize($_POST['department'] ?? '');
    $semester   = sanitize($_POST['semester'] ?? '');
    $batch      = sanitize($_POST['batch'] ?? '');
    $status     = sanitize($_POST['status'] ?? 'active');
    $password   = $_POST['password'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF mismatch).';
    } elseif (empty($name) || empty($studentId) || empty($email) || empty($password)) {
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
                // Check unique student_id
                $chkId = $db->prepare("SELECT id FROM students WHERE student_id = :sid");
                $chkId->execute([':sid' => $studentId]);
                if ($chkId->fetch()) {
                    $error = 'This Student ID is already registered.';
                } else {
                    $db->beginTransaction();
                    $pwHash = password_hash($password, PASSWORD_DEFAULT);
                    $uStmt = $db->prepare("
                        INSERT INTO users (name, email, password, role, status, created_at)
                        VALUES (:name, :email, :pass, 'student', :status, NOW())
                    ");
                    $uStmt->execute([
                        ':name'   => $name,
                        ':email'  => $email,
                        ':pass'   => $pwHash,
                        ':status' => $status
                    ]);
                    $newUserId = (int)$db->lastInsertId();

                    $sStmt = $db->prepare("
                        INSERT INTO students (user_id, student_id, semester, department, batch, phone, created_at)
                        VALUES (:uid, :sid, :sem, :dept, :batch, :phone, NOW())
                    ");
                    $sStmt->execute([
                        ':uid'   => $newUserId,
                        ':sid'   => $studentId,
                        ':sem'   => $semester,
                        ':dept'  => $department,
                        ':batch' => $batch,
                        ':phone' => $phone
                    ]);
                    $db->commit();

                    redirect(BASE_URL . 'admin/students/index.php', 'Student ' . $name . ' enrolled successfully.', 'success');
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Enroll New Student";
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Enroll New Student</h1>
                <p class="page-subtitle">Register a student and issue login credentials</p>
            </div>
            <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Students
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
                        <h5 class="fw-bold mb-0"><i class="fas fa-user-graduate text-primary me-2"></i> Student Enrollment Form</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>admin/students/create.php" method="POST">
                            <?= csrfField() ?>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student Name *</label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. Jordan Miller" value="<?= e($_POST['name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student ID / Roll No. *</label>
                                    <input type="text" name="student_id" class="form-control" placeholder="e.g. STU-2024-CS88" value="<?= e($_POST['student_id'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Academic Email *</label>
                                    <input type="email" name="email" class="form-control" placeholder="student@collegenotes.edu" value="<?= e($_POST['email'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="+1 555-0155" value="<?= e($_POST['phone'] ?? '') ?>">
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
                                    <label class="form-label small fw-semibold">Semester *</label>
                                    <select name="semester" class="form-select" required>
                                        <option value="">Select Semester...</option>
                                        <?php foreach ($semesters as $s): ?>
                                            <option value="<?= e($s['semester_name']) ?>"><?= e($s['semester_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Batch *</label>
                                    <input type="text" name="batch" class="form-control" placeholder="e.g. 2024-2028" value="<?= e($_POST['batch'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Initial Password *</label>
                                    <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="blocked">Blocked</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="fas fa-save me-1"></i> Enroll Student
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
