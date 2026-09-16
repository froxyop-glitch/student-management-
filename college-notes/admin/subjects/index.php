<?php
/**
 * College Notes Management System
 * Admin - Subject Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$error = '';
$success = '';

// Handle Create / Update Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!verifyCsrfToken($token)) {
        $error = 'Security validation failed (CSRF mismatch).';
    } elseif ($action === 'create') {
        $name = sanitize($_POST['name'] ?? '');
        $code = strtoupper(sanitize($_POST['code'] ?? ''));
        $semester = sanitize($_POST['semester'] ?? '');
        $dept = sanitize($_POST['department'] ?? '');

        if (empty($name) || empty($code) || empty($semester) || empty($dept)) {
            $error = 'Please fill in all subject fields.';
        } else {
            try {
                $ins = $db->prepare("INSERT INTO subjects (name, code, semester, department, created_at) VALUES (:n, :c, :s, :d, NOW())");
                $ins->execute([':n' => $name, ':c' => $code, ':s' => $semester, ':d' => $dept]);
                $success = 'Subject "' . $name . '" created successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to create subject (code may already exist).';
            }
        }
    } elseif ($action === 'delete') {
        $sid = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($sid) {
            try {
                $del = $db->prepare("DELETE FROM subjects WHERE id = :id");
                $del->execute([':id' => $sid]);
                $success = 'Subject deleted successfully.';
            } catch (PDOException $e) {
                $error = 'Cannot delete subject: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all subjects with notes counts
$subjects = $db->query("
    SELECT s.*, (SELECT COUNT(*) FROM notes n WHERE n.subject_id = s.id) as notes_count
    FROM subjects s
    ORDER BY s.department ASC, s.semester ASC, s.name ASC
")->fetchAll();

$semesters = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

$pageTitle = "Subjects Catalog Management";
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php displayFlashMessage(); ?>

        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Curriculum Subjects Management</h1>
                <p class="page-subtitle">Configure academic courses, subject codes, and curriculum branches</p>
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
            <!-- Add New Subject Form -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-plus-circle text-primary me-2"></i> Add New Subject</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>admin/subjects/index.php" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="create">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Subject Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Distributed Operating Systems" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Subject Code *</label>
                                <input type="text" name="code" class="form-control" placeholder="e.g. CS702" required>
                            </div>

                            <div class="mb-3">
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

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Semester *</label>
                                <select name="semester" class="form-select" required>
                                    <option value="">Select Semester...</option>
                                    <?php foreach ($semesters as $s): ?>
                                        <option value="<?= e($s['semester_name']) ?>"><?= e($s['semester_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="fas fa-save me-1"></i> Save Subject
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Existing Subjects Table -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-layer-group text-primary me-2"></i> Registered Academic Subjects</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Subject Name</th>
                                        <th>Department</th>
                                        <th>Semester</th>
                                        <th>Notes Count</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $sub): ?>
                                        <tr>
                                            <td><span class="badge bg-secondary"><?= e($sub['code']) ?></span></td>
                                            <td class="fw-bold text-dark"><?= e($sub['name']) ?></td>
                                            <td><?= e($sub['department']) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= e($sub['semester']) ?></span></td>
                                            <td class="fw-bold text-primary"><?= $sub['notes_count'] ?> notes</td>
                                            <td class="text-end">
                                                <form action="<?= BASE_URL ?>admin/subjects/index.php" method="POST" class="d-inline confirm-delete-form" data-item-name="<?= e($sub['name']) ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Subject">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
