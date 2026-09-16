<?php
/**
 * College Notes Management System
 * Admin - Edit Academic Note
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$db = getDB();
$noteId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$noteId) {
    redirect(BASE_URL . 'admin/notes/index.php', 'Invalid note specified.', 'danger');
}

$stmt = $db->prepare("SELECT * FROM notes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $noteId]);
$note = $stmt->fetch();

if (!$note) {
    redirect(BASE_URL . 'admin/notes/index.php', 'Note record not found.', 'danger');
}

$error = '';
$subjects = $db->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();
$semesters = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $subjectId   = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);
    $semester    = sanitize($_POST['semester'] ?? '');
    $department  = sanitize($_POST['department'] ?? '');
    $status      = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived']) ? $_POST['status'] : 'published';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (CSRF mismatch).';
    } elseif (empty($title) || empty($subjectId) || empty($semester) || empty($department)) {
        $error = 'Please fill in all mandatory fields.';
    } else {
        try {
            $uStmt = $db->prepare("
                UPDATE notes SET
                    subject_id = :sid,
                    title = :title,
                    description = :desc,
                    semester = :sem,
                    department = :dept,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $uStmt->execute([
                ':sid'   => $subjectId,
                ':title' => $title,
                ':desc'  => $description,
                ':sem'   => $semester,
                ':dept'  => $department,
                ':status'=> $status,
                ':id'    => $noteId
            ]);

            redirect(BASE_URL . 'admin/notes/index.php', 'Note "' . $title . '" updated successfully.', 'success');
        } catch (PDOException $e) {
            error_log("Admin note update error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$typeInfo = getFileTypeInfo($note['file_type']);
$pageTitle = "Moderate Note: " . $note['title'];
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Moderate Note</h1>
                <p class="page-subtitle">Review content, change approval state, or correct department tags</p>
            </div>
            <a href="<?= BASE_URL ?>admin/notes/index.php" class="btn btn-outline-secondary">
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
                        <h5 class="fw-bold mb-0"><i class="fas fa-file-signature text-primary me-2"></i> Note Details</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>admin/notes/edit.php?id=<?= $noteId ?>" method="POST">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Note Title *</label>
                                <input type="text" name="title" class="form-control" value="<?= e($note['title']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Subject *</label>
                                <select name="subject_id" class="form-select" required>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($note['subject_id'] == $s['id']) ? 'selected' : '' ?>>
                                            <?= e($s['code']) ?> - <?= e($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="Computer Science" <?= ($note['department'] === 'Computer Science') ? 'selected' : '' ?>>Computer Science</option>
                                        <option value="Electronics & Communication" <?= ($note['department'] === 'Electronics & Communication') ? 'selected' : '' ?>>Electronics & Communication</option>
                                        <option value="Information Technology" <?= ($note['department'] === 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
                                        <option value="Mechanical Engineering" <?= ($note['department'] === 'Mechanical Engineering') ? 'selected' : '' ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?= ($note['department'] === 'Civil Engineering') ? 'selected' : '' ?>>Civil Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Semester *</label>
                                    <select name="semester" class="form-select" required>
                                        <?php foreach ($semesters as $sem): ?>
                                            <option value="<?= e($sem['semester_name']) ?>" <?= ($note['semester'] === $sem['semester_name']) ? 'selected' : '' ?>><?= e($sem['semester_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Description / Syllabus Scope</label>
                                <textarea name="description" class="form-control" rows="4"><?= e($note['description']) ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Approval & Moderation Status</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="stPubAdmin" value="published" <?= $note['status'] === 'published' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stPubAdmin">
                                            <span class="badge bg-success me-1">Published</span> (Approved for student access)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="stDraftAdmin" value="draft" <?= $note['status'] === 'draft' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stDraftAdmin">
                                            <span class="badge bg-warning text-dark me-1">Draft</span> (Hidden from public view)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="stArchAdmin" value="archived" <?= $note['status'] === 'archived' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stArchAdmin">
                                            <span class="badge bg-secondary me-1">Archived</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>admin/notes/index.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="fas fa-save me-1"></i> Save Changes
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
