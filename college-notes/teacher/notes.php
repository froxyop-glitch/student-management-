<?php
/**
 * College Notes Management System
 * Teacher Notes Management (List, Filter, Search)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['teacher', 'admin']);

$user = currentUser();
$db = getDB();

// Fetch Teacher ID
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$teacherId = (int)$stmt->fetchColumn();

// Search & Filters
$search    = sanitize($_GET['search'] ?? '');
$subjectId = filter_var($_GET['subject'] ?? null, FILTER_VALIDATE_INT);
$status    = sanitize($_GET['status'] ?? '');
$semester  = sanitize($_GET['semester'] ?? '');

$where = ["n.teacher_id = :tid"];
$params = [':tid' => $teacherId];

if (!empty($search)) {
    $where[] = "(n.title LIKE :search OR n.description LIKE :search OR s.name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($subjectId)) {
    $where[] = "n.subject_id = :subject_id";
    $params[':subject_id'] = $subjectId;
}
if (!empty($status)) {
    $where[] = "n.status = :status";
    $params[':status'] = $status;
}
if (!empty($semester)) {
    $where[] = "n.semester = :semester";
    $params[':semester'] = $semester;
}

$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT n.*, s.name as subject_name, s.code as subject_code,
           (SELECT COUNT(*) FROM downloads d WHERE d.note_id = n.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    WHERE $whereSql
    ORDER BY n.created_at DESC
");
$stmt->execute($params);
$notes = $stmt->fetchAll();

// Dropdowns for filtering
$subjects = $db->query("SELECT id, name, code FROM subjects ORDER BY name ASC")->fetchAll();
$semesters = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

$pageTitle = "My Uploaded Notes";
$customCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php displayFlashMessage(); ?>

        <div class="page-header-bar">
            <div>
                <h1 class="page-title">My Uploaded Notes</h1>
                <p class="page-subtitle">Manage, update, and organize your academic course materials</p>
            </div>
            <a href="<?= BASE_URL ?>teacher/create-note.php" class="btn btn-success fw-bold shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> Upload New Note
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card">
            <form action="<?= BASE_URL ?>teacher/notes.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Search Title</label>
                    <input type="text" name="search" class="form-control" placeholder="Search keywords..." value="<?= e($search) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Subject</label>
                    <select name="subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $subjectId == $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['code']) ?> - <?= e($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= e($sem['semester_name']) ?>" <?= $semester === $sem['semester_name'] ? 'selected' : '' ?>><?= e($sem['semester_name']) ?></option>
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
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="<?= BASE_URL ?>teacher/notes.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Notes Table -->
        <div class="content-card">
            <div class="card-body p-0">
                <?php if (empty($notes)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
                        <h5 class="fw-bold">No notes found</h5>
                        <p class="small mb-3">Upload your first course syllabus or lecture slides now.</p>
                        <a href="<?= BASE_URL ?>teacher/create-note.php" class="btn btn-primary btn-sm">Upload Note</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Document Title</th>
                                    <th>Subject & Code</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>File Details</th>
                                    <th>Downloads</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notes as $n): ?>
                                    <?php $typeInfo = getFileTypeInfo($n['file_type']); ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($n['title']) ?></div>
                                            <small class="text-muted"><?= e(mb_strimwidth($n['description'] ?? '', 0, 70, '...')) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary me-1"><?= e($n['subject_code']) ?></span>
                                            <?= e($n['subject_name']) ?>
                                        </td>
                                        <td><?= e($n['semester']) ?></td>
                                        <td>
                                            <span class="badge <?= match($n['status']) {
                                                'published' => 'bg-success',
                                                'draft' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            } ?>">
                                                <?= e($n['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $typeInfo['badge'] ?> text-uppercase"><?= e($n['file_type']) ?></span>
                                            <span class="small text-muted ms-1"><?= formatBytes($n['file_size']) ?></span>
                                        </td>
                                        <td class="fw-bold text-primary"><?= $n['download_count'] ?></td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View Preview">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>teacher/edit-note.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Note">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="<?= BASE_URL ?>teacher/delete-note.php" method="POST" class="d-inline confirm-delete-form" data-item-name="<?= e($n['title']) ?>">
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
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
