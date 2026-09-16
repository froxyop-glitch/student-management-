<?php
/**
 * College Notes Management System
 * Edit Academic Note (Strict Ownership Enforced)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['teacher', 'admin']);

$user = currentUser();
$db = getDB();

$noteId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$noteId) {
    redirect(BASE_URL . 'teacher/notes.php', 'Invalid note specified.', 'danger');
}

// Fetch Note
$stmt = $db->prepare("SELECT * FROM notes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $noteId]);
$note = $stmt->fetch();

if (!$note) {
    redirect(BASE_URL . 'teacher/notes.php', 'Note not found.', 'danger');
}

// Enforce Ownership: Teacher can only edit their own note
if ($user['role'] === 'teacher') {
    $tStmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1");
    $tStmt->execute([':uid' => $user['id']]);
    $myTeacherId = (int)$tStmt->fetchColumn();

    if ($note['teacher_id'] != $myTeacherId) {
        redirect(BASE_URL . 'teacher/notes.php', 'Access denied. You can only edit notes you uploaded.', 'danger');
    }
}

$error = '';
$subjects = $db->query("SELECT * FROM subjects ORDER BY name ASC")->fetchAll();
$semesters = $db->query("SELECT * FROM semesters ORDER BY semester_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken   = $_POST['csrf_token'] ?? '';
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $subjectId   = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);
    $semester    = sanitize($_POST['semester'] ?? '');
    $department  = sanitize($_POST['department'] ?? '');
    $status      = in_array($_POST['status'] ?? '', ['published', 'draft', 'archived']) ? $_POST['status'] : 'published';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation failed (Invalid CSRF Token).';
    } elseif (empty($title) || empty($subjectId) || empty($semester) || empty($department)) {
        $error = 'Please fill in all mandatory fields.';
    } else {
        $updatedFile = false;
        $fileDetails = [];

        // Check if user uploaded a replacement file
        if (isset($_FILES['note_file']) && $_FILES['note_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = validateAndSaveNoteUpload($_FILES['note_file']);
            if (!$uploadResult['success']) {
                $error = $uploadResult['error'];
            } else {
                $updatedFile = true;
                $fileDetails = $uploadResult;
            }
        }

        if (empty($error)) {
            try {
                if ($updatedFile) {
                    // Update metadata + new file fields
                    $uStmt = $db->prepare("
                        UPDATE notes SET
                            subject_id = :sid,
                            title = :title,
                            description = :desc,
                            file_name = :fname,
                            file_path = :fpath,
                            file_type = :ftype,
                            file_size = :fsize,
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
                        ':fname' => $fileDetails['original_name'],
                        ':fpath' => $fileDetails['relative_path'],
                        ':ftype' => $fileDetails['file_type'],
                        ':fsize' => $fileDetails['file_size'],
                        ':sem'   => $semester,
                        ':dept'  => $department,
                        ':status'=> $status,
                        ':id'    => $noteId
                    ]);

                    // Remove old physical file if exists
                    $oldPath = realpath(BASE_PATH . $note['file_path']);
                    if ($oldPath && file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                } else {
                    // Update metadata only
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
                }

                redirect(BASE_URL . 'teacher/notes.php', 'Note "' . $title . '" updated successfully!', 'success');
            } catch (PDOException $e) {
                error_log("Note update error: " . $e->getMessage());
                $error = 'Database error updating note: ' . $e->getMessage();
            }
        }
    }
}

$typeInfo = getFileTypeInfo($note['file_type']);
$pageTitle = "Edit Note: " . $note['title'];
$customCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Edit Academic Note</h1>
                <p class="page-subtitle">Modify course note content, details or replace file</p>
            </div>
            <a href="<?= BASE_URL ?>teacher/notes.php" class="btn btn-outline-secondary">
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
                        <h5 class="fw-bold mb-0"><i class="fas fa-edit text-primary me-2"></i> Note Properties</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>teacher/edit-note.php?id=<?= $noteId ?>" method="POST" enctype="multipart/form-data">
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
                                <label class="form-label small fw-semibold">Description / Lecture Summary</label>
                                <textarea name="description" class="form-control" rows="4"><?= e($note['description']) ?></textarea>
                            </div>

                            <!-- Current File & Optional Replacement -->
                            <div class="mb-4 p-3 bg-light rounded-3 border">
                                <label class="form-label small fw-semibold mb-1">Attached Document</label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge <?= $typeInfo['badge'] ?> text-uppercase"><?= e($note['file_type']) ?></span>
                                    <span class="fw-semibold text-dark"><?= e($note['file_name']) ?></span>
                                    <span class="text-muted small">(<?= formatBytes($note['file_size']) ?>)</span>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label small text-muted">Upload Replacement File (Leave blank to keep existing file):</label>
                                    <input type="file" name="note_file" class="form-control form-control-sm validate-file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Status</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="stPub" value="published" <?= $note['status'] === 'published' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stPub">
                                            <span class="badge bg-success me-1">Published</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="stDraft" value="draft" <?= $note['status'] === 'draft' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stDraft">
                                            <span class="badge bg-warning text-dark me-1">Draft</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="stArch" value="archived" <?= $note['status'] === 'archived' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stArch">
                                            <span class="badge bg-secondary me-1">Archived</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Last updated: <?= date('M j, Y g:i A', strtotime($note['updated_at'])) ?></span>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>teacher/notes.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary fw-bold px-4">
                                        <i class="fas fa-save me-1"></i> Update Note
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
