<?php
/**
 * College Notes Management System
 * Detailed Note View & PDF Previewer
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$noteId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$noteId) {
    redirect(BASE_URL . 'user/notes.php', 'Invalid note specified.', 'danger');
}

$db = getDB();
$stmt = $db->prepare("
    SELECT n.*, s.name as subject_name, s.code as subject_code, 
           u.name as teacher_name, u.email as teacher_email, t.designation as teacher_designation,
           (SELECT COUNT(*) FROM downloads d WHERE d.note_id = n.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE n.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $noteId]);
$note = $stmt->fetch();

if (!$note) {
    http_response_code(404);
    include __DIR__ . '/../404.php';
    exit;
}

// Check authorization if note is draft or archived
if ($note['status'] !== 'published') {
    $user = currentUser();
    $role = currentRole();
    $canView = ($role === 'admin') || ($role === 'teacher' && ($user['profile_id'] ?? null) == $note['teacher_id']);
    if (!$canView) {
        redirect(BASE_URL . 'user/notes.php', 'This note is currently pending review or unpublished.', 'warning');
    }
}

$typeInfo = getFileTypeInfo($note['file_type']);
$pageTitle = $note['title'];
$customCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="<?= isLoggedIn() ? 'dashboard-layout' : 'container py-4' ?>">
    <?php if (isLoggedIn()): ?>
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="<?= isLoggedIn() ? 'main-content' : '' ?>">
        <?php displayFlashMessage(); ?>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>user/notes.php" class="text-decoration-none">Notes</a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?= e($note['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Left Column: Note Details & PDF Viewer -->
            <div class="col-lg-8">
                <div class="content-card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge <?= $typeInfo['badge'] ?> text-uppercase"><?= e($note['file_type']) ?></span>
                                <span class="badge bg-secondary"><?= e($note['semester']) ?></span>
                                <span class="badge bg-info text-dark"><?= e($note['department']) ?></span>
                            </div>
                            <h2 class="h3 fw-bold mb-1 text-navy"><?= e($note['title']) ?></h2>
                            <div class="text-muted small">
                                <span>Subject: <strong><?= e($note['subject_name']) ?> (<?= e($note['subject_code']) ?>)</strong></span>
                            </div>
                        </div>
                        <div>
                            <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $note['id'] ?>" class="btn btn-primary px-4 shadow-sm">
                                <i class="fas fa-download me-2"></i> Download Note
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <h6 class="fw-bold text-secondary mb-2">Description / Lecture Summary:</h6>
                        <div class="bg-light p-3 rounded-3 text-muted mb-4 border">
                            <?= nl2br(e($note['description'] ?: 'No additional description provided for this academic resource.')) ?>
                        </div>

                        <!-- Embedded PDF Viewer if PDF -->
                        <?php if (strtolower($note['file_type']) === 'pdf'): ?>
                            <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-eye me-1 text-danger"></i> Document Preview:</h6>
                            <div class="document-preview-container shadow-sm border">
                                <iframe src="<?= BASE_URL . e($note['file_path']) ?>#toolbar=1" type="application/pdf">
                                    <p class="p-4 text-center text-white">
                                        Your browser does not support inline PDF viewing. 
                                        <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $note['id'] ?>" class="btn btn-warning btn-sm ms-2">
                                            Download file to view
                                        </a>
                                    </p>
                                </iframe>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border p-4 text-center rounded-3">
                                <div class="note-icon-large <?= $typeInfo['color'] ?> mb-2">
                                    <i class="fas <?= $typeInfo['icon'] ?> fa-3x"></i>
                                </div>
                                <h5 class="fw-bold">Preview not available for .<?= e($note['file_type']) ?> files</h5>
                                <p class="text-muted small mb-3">Download the file to open it in Microsoft Word, PowerPoint, or your local editor.</p>
                                <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $note['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-1"></i> Download <?= e($note['file_name']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Note Metadata & Faculty Details -->
            <div class="col-lg-4">
                <!-- File Attributes -->
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-info-circle text-primary me-2"></i> File Information</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Original File Name</span>
                                <span class="fw-semibold text-truncate ms-2" style="max-width: 180px;"><?= e($note['file_name']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">File Format</span>
                                <span class="badge <?= $typeInfo['badge'] ?> text-uppercase"><?= e($note['file_type']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">File Size</span>
                                <span class="fw-semibold"><?= formatBytes($note['file_size']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Total Downloads</span>
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1"><?= $note['download_count'] ?> times</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Date Published</span>
                                <span class="fw-semibold"><?= date('F j, Y', strtotime($note['created_at'])) ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer p-3 bg-white">
                        <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $note['id'] ?>" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-file-download me-2"></i> Download File
                        </a>
                    </div>
                </div>

                <!-- Instructor Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher text-success me-2"></i> Faculty Instructor</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon green" style="width: 50px; height: 50px;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?= e($note['teacher_name']) ?></h6>
                                <small class="text-muted"><?= e($note['teacher_designation']) ?></small>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">
                            <i class="fas fa-envelope me-1"></i> <?= e($note['teacher_email']) ?>
                        </p>
                        <a href="<?= BASE_URL ?>user/notes.php?teacher=<?= $note['teacher_id'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                            More Notes from This Faculty
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
