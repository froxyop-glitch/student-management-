<?php
/**
 * College Notes Management System
 * Admin - Teacher Management (List & Search)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$dept = sanitize($_GET['department'] ?? '');

$where = ["u.role = 'teacher'"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE :search OR u.email LIKE :search OR t.employee_id LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($dept)) {
    $where[] = "t.department = :dept";
    $params[':dept'] = $dept;
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT t.*, u.id as user_id, u.name, u.email, u.status, u.created_at as joined_date,
           (SELECT COUNT(*) FROM notes n WHERE n.teacher_id = t.id) as notes_count
    FROM teachers t
    JOIN users u ON t.user_id = u.id
    WHERE $whereSql
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$departments = $db->query("SELECT DISTINCT department FROM teachers ORDER BY department ASC")->fetchAll();

$pageTitle = "Faculty Management";
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
                <h1 class="page-title">Faculty Management</h1>
                <p class="page-subtitle">Add, edit, audit, and manage teaching staff</p>
            </div>
            <a href="<?= BASE_URL ?>admin/teachers/create.php" class="btn btn-success fw-bold shadow-sm">
                <i class="fas fa-user-plus me-1"></i> + Add New Teacher
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card">
            <form action="<?= BASE_URL ?>admin/teachers/index.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Search Faculty</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, employee ID, or email..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d['department']) ?>" <?= $dept === $d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Teachers Table -->
        <div class="content-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name & ID</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Status</th>
                                <th>Notes Uploaded</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No faculty members found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($t['name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= e($t['employee_id']) ?></span>
                                        </td>
                                        <td><?= e($t['email']) ?></td>
                                        <td><?= e($t['department']) ?></td>
                                        <td><?= e($t['designation']) ?></td>
                                        <td>
                                            <span class="badge <?= match($t['status']) {
                                                'active' => 'status-badge-active',
                                                'inactive' => 'status-badge-inactive',
                                                default => 'status-badge-blocked'
                                            } ?>">
                                                <?= e($t['status']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-primary"><?= $t['notes_count'] ?> notes</td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= BASE_URL ?>admin/teachers/edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Teacher">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="<?= BASE_URL ?>admin/teachers/delete.php" method="POST" class="d-inline confirm-delete-form" data-item-name="<?= e($t['name']) ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Teacher">
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
