<?php
/**
 * College Notes Management System
 * Admin - Notes Moderation & Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$search = sanitize($_GET['search'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$dept   = sanitize($_GET['department'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(n.title LIKE :search OR s.name LIKE :search OR u.name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($status)) {
    $where[] = "n.status = :status";
    $params[':status'] = $status;
}
if (!empty($dept)) {
    $where[] = "n.department = :dept";
    $params[':dept'] = $dept;
}

$whereSql = implode(' AND ', $where);

// Quick status toggle action (Approve / Unpublish)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $token = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($token)) {
        $nid = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $newStatus = in_array($_POST['new_status'] ?? '', ['published', 'draft', 'archived']) ? $_POST['new_status'] : 'published';
        if ($nid) {
            $upd = $db->prepare("UPDATE notes SET status = :st WHERE id = :id");
            $upd->execute([':st' => $newStatus, ':id' => $nid]);
            redirect(BASE_URL . 'admin/notes/index.php', 'Note status updated to ' . ucfirst($newStatus) . '.', 'success');
        }
    }
}

$stmt = $db->prepare("
    SELECT n.*, s.name as subject_name, s.code as subject_code, u.name as teacher_name,
           (SELECT COUNT(*) FROM downloads d WHERE d.note_id = n.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE $whereSql
    ORDER BY n.created_at DESC
");
$stmt->execute($params);
$notes = $stmt->fetchAll();

$departments = $db->query("SELECT DISTINCT department FROM notes ORDER BY department ASC")->fetchAll();

$pageTitle = "Academic Notes Moderation";
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
                <h1 class="page-title">Academic Notes Moderation</h1>
                <p class="page-subtitle">Review, publish, unpublish, and delete inappropriate notes</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card">
            <form action="<?= BASE_URL ?>admin/notes/index.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Search Notes</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by title, subject, or teacher..." value="<?= e($search) ?>">
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
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="<?= BASE_URL ?>admin/notes/index.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Notes Table -->
        <div class="content-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Note Title</th>
                                <th>Subject</th>
                                <th>Faculty</th>
                                <th>Semester</th>
                                <th>File Details</th>
                                <th>Status</th>
                                <th>Downloads</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No notes found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($notes as $n): ?>
                                    <?php $typeInfo = getFileTypeInfo($n['file_type']); ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 220px;"><?= e($n['title']) ?></div>
                                            <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary me-1"><?= e($n['subject_code']) ?></span>
                                            <?= e($n['subject_name']) ?>
                                        </td>
                                        <td><?= e($n['teacher_name']) ?></td>
                                        <td><?= e($n['semester']) ?></td>
                                        <td>
                                            <span class="badge <?= $typeInfo['badge'] ?> text-uppercase"><?= e($n['file_type']) ?></span>
                                            <span class="small text-muted ms-1"><?= formatBytes($n['file_size']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($n['status'] === 'published'): ?>
                                                <form action="<?= BASE_URL ?>admin/notes/index.php" method="POST" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                                    <input type="hidden" name="new_status" value="draft">
                                                    <button type="submit" class="btn btn-sm btn-outline-success p-1 py-0" title="Click to unpublish">
                                                        <i class="fas fa-check-circle me-1"></i> Published
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="<?= BASE_URL ?>admin/notes/index.php" method="POST" class="d-inline">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                                    <input type="hidden" name="new_status" value="published">
                                                    <button type="submit" class="btn btn-sm btn-warning text-dark p-1 py-0 fw-bold" title="Click to Approve & Publish">
                                                        <i class="fas fa-arrow-alt-circle-up me-1"></i> Approve
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-primary"><?= $n['download_count'] ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View Note">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>admin/notes/edit.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Note">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="<?= BASE_URL ?>admin/notes/delete.php" method="POST" class="d-inline confirm-delete-form" data-item-name="<?= e($n['title']) ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Note">
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
