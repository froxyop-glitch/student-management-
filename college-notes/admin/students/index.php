<?php
/**
 * College Notes Management System
 * Admin - Student Management (List & Filter)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$dept   = sanitize($_GET['department'] ?? '');
$sem    = sanitize($_GET['semester'] ?? '');

$where = ["u.role = 'student'"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE :search OR u.email LIKE :search OR s.student_id LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($dept)) {
    $where[] = "s.department = :dept";
    $params[':dept'] = $dept;
}
if (!empty($sem)) {
    $where[] = "s.semester = :sem";
    $params[':sem'] = $sem;
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.*, u.id as user_id, u.name, u.email, u.status, u.created_at as enrolled_date,
           (SELECT COUNT(*) FROM downloads d WHERE d.user_id = u.id) as downloads_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    WHERE $whereSql
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

$departments = $db->query("SELECT DISTINCT department FROM students ORDER BY department ASC")->fetchAll();
$semesters = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

$pageTitle = "Student Roster Management";
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
                <h1 class="page-title">Student Roster Management</h1>
                <p class="page-subtitle">Enroll, edit, status verify, and manage enrolled college students</p>
            </div>
            <a href="<?= BASE_URL ?>admin/students/create.php" class="btn btn-primary fw-bold shadow-sm">
                <i class="fas fa-user-plus me-1"></i> + Enroll Student
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card">
            <form action="<?= BASE_URL ?>admin/students/index.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Search Students</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, student ID, roll no., or email..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d['department']) ?>" <?= $dept === $d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= e($s['semester_name']) ?>" <?= $sem === $s['semester_name'] ? 'selected' : '' ?>><?= e($s['semester_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Students Table -->
        <div class="content-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student Name & ID</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Semester</th>
                                <th>Batch</th>
                                <th>Status</th>
                                <th>Downloads</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No students matching criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $stu): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($stu['name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= e($stu['student_id']) ?></span>
                                        </td>
                                        <td><?= e($stu['email']) ?></td>
                                        <td><?= e($stu['department']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e($stu['semester']) ?></span></td>
                                        <td><?= e($stu['batch']) ?></td>
                                        <td>
                                            <span class="badge <?= match($stu['status']) {
                                                'active' => 'status-badge-active',
                                                'inactive' => 'status-badge-inactive',
                                                default => 'status-badge-blocked'
                                            } ?>">
                                                <?= e($stu['status']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-success"><?= $stu['downloads_count'] ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= BASE_URL ?>admin/students/edit.php?id=<?= $stu['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="<?= BASE_URL ?>admin/students/delete.php" method="POST" class="d-inline confirm-delete-form" data-item-name="<?= e($stu['name']) ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= $stu['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Student">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
