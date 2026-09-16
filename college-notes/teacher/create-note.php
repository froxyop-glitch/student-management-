<?php
/**
 * College Notes Management System
 * Create & Upload Academic Note
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['teacher', 'admin']);

$user = currentUser();
$db = getDB();

// Fetch Teacher Record
$stmt = $db->prepare("SELECT * FROM teachers WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$teacher = $stmt->fetch();

$teacherId = $teacher['id'] ?? 1;
$teacherDept = $teacher['department'] ?? '';

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
        $error = 'Security validation failed (Invalid CSRF Token). Please resubmit.';
    } elseif (empty($title) || empty($subjectId) || empty($semester) || empty($department)) {
        $error = 'Please fill in all mandatory fields (Title, Subject, Semester, Department).';
    } elseif (!isset($_FILES['note_file']) || $_FILES['note_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please choose a document file to upload.';
    } else {
        // Validate and save file
        $uploadResult = validateAndSaveNoteUpload($_FILES['note_file']);

        if (!$uploadResult['success']) {
            $error = $uploadResult['error'];
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO notes 
                    (teacher_id, subject_id, title, description, file_name, file_path, file_type, file_size, semester, department, status, created_at)
                    VALUES 
                    (:tid, :sid, :title, :desc, :fname, :fpath, :ftype, :fsize, :sem, :dept, :status, NOW())
                ");

                $stmt->execute([
                    ':tid'   => $teacherId,
                    ':sid'   => $subjectId,
                    ':title' => $title,
                    ':desc'  => $description,
                    ':fname' => $uploadResult['original_name'],
                    ':fpath' => $uploadResult['relative_path'],
                    ':ftype' => $uploadResult['file_type'],
                    ':fsize' => $uploadResult['file_size'],
                    ':sem'   => $semester,
                    ':dept'  => $department,
                    ':status'=> $status
                ]);

                redirect(BASE_URL . 'teacher/notes.php', 'Note "' . $title . '" uploaded successfully!', 'success');
            } catch (PDOException $e) {
                error_log("Note creation error: " . $e->getMessage());
                // Remove uploaded file on DB failure
                @unlink(BASE_PATH . $uploadResult['relative_path']);
                $error = 'Database error while saving note record: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Upload Academic Note";
$customCss = ['dashboard.css'];
$customJs = ['dashboard.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Upload Academic Note</h1>
                <p class="page-subtitle">Publish syllabus materials, lecture presentations, and tutorials</p>
            </div>
            <a href="<?= BASE_URL ?>teacher/notes.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Notes
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
                        <h5 class="fw-bold mb-0"><i class="fas fa-file-upload text-success me-2"></i> Note Details & Document File</h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= BASE_URL ?>teacher/create-note.php" method="POST" enctype="multipart/form-data">
                            <?= csrfField() ?>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Note Title *</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Module 3: Advanced SQL Joins & Query Optimization" value="<?= e($_POST['title'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Course / Subject *</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">Select Target Subject...</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= (($_POST['subject_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                                            <?= e($s['code']) ?> - <?= e($s['name']) ?> (<?= e($s['department']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="">Select Department...</option>
                                        <option value="Computer Science" <?= ($teacherDept === 'Computer Science' || ($_POST['department'] ?? '') === 'Computer Science') ? 'selected' : '' ?>>Computer Science</option>
                                        <option value="Electronics & Communication" <?= ($teacherDept === 'Electronics & Communication' || ($_POST['department'] ?? '') === 'Electronics & Communication') ? 'selected' : '' ?>>Electronics & Communication</option>
                                        <option value="Information Technology" <?= ($teacherDept === 'Information Technology' || ($_POST['department'] ?? '') === 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
                                        <option value="Mechanical Engineering" <?= ($teacherDept === 'Mechanical Engineering' || ($_POST['department'] ?? '') === 'Mechanical Engineering') ? 'selected' : '' ?>>Mechanical Engineering</option>
                                        <option value="Civil Engineering" <?= ($teacherDept === 'Civil Engineering' || ($_POST['department'] ?? '') === 'Civil Engineering') ? 'selected' : '' ?>>Civil Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Semester *</label>
                                    <select name="semester" class="form-select" required>
                                        <option value="">Select Semester...</option>
                                        <?php foreach ($semesters as $sem): ?>
                                            <option value="<?= e($sem['semester_name']) ?>" <?= (($_POST['semester'] ?? '') === $sem['semester_name']) ? 'selected' : '' ?>><?= e($sem['semester_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Detailed Description / Instructions</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Summarize key topics covered, exam hints, or required prerequisites..."><?= e($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <!-- File Upload Field -->
                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Document File * (Max 20MB)</label>
                                <input type="file" name="note_file" class="form-control validate-file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required>
                                <div class="form-text small mt-1">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Supported formats: <strong>PDF, DOC, DOCX, PPT, PPTX, TXT</strong>. Securely sanitized upon upload.
                                </div>
                                <div id="selectedFileInfo" class="small text-success fw-semibold mt-1 d-none"></div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Publishing Status</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusPub" value="published" checked>
                                        <label class="form-check-label" for="statusPub">
                                            <span class="badge bg-success me-1">Published</span> (Instantly visible to students)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusDraft" value="draft">
                                        <label class="form-check-label" for="statusDraft">
                                            <span class="badge bg-warning text-dark me-1">Draft</span> (Saved privately for later)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= BASE_URL ?>teacher/notes.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-success fw-bold px-4">
                                    <i class="fas fa-cloud-upload-alt me-1"></i> Upload & Save Note
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Upload Guidelines Sidebar -->
            <div class="col-lg-3">
                <div class="content-card">
                    <div class="card-header">
                        <h6 class="fw-bold mb-0"><i class="fas fa-lightbulb text-warning me-1"></i> Best Practices</h6>
                    </div>
                    <div class="card-body small text-muted">
                        <p class="mb-2"><strong>Descriptive Titles:</strong> Include the chapter number and specific topic name.</p>
                        <p class="mb-2"><strong>PDF Preferred:</strong> PDF files render natively in student browsers for faster revision.</p>
                        <p class="mb-0"><strong>File Size:</strong> Compress large scanned images before uploading to save student bandwidth.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
