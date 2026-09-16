<?php
/**
 * College Notes Management System
 * Teacher / Faculty Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['teacher', 'admin']);

$user = currentUser();
$db = getDB();

// Fetch Teacher Profile
$stmt = $db->prepare("SELECT * FROM teachers WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$teacher = $stmt->fetch();

if (!$teacher && $user['role'] === 'teacher') {
    die("Teacher profile not linked to user account. Contact administrator.");
}
$teacherId = $teacher['id'] ?? 0;

// Teacher Statistics
$stmt = $db->prepare("SELECT COUNT(*) FROM notes WHERE teacher_id = :tid");
$stmt->execute([':tid' => $teacherId]);
$totalNotes = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM notes WHERE teacher_id = :tid AND status = 'published'");
$stmt->execute([':tid' => $teacherId]);
$publishedNotes = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM notes WHERE teacher_id = :tid AND status = 'draft'");
$stmt->execute([':tid' => $teacherId]);
$draftNotes = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(d.id) 
    FROM downloads d 
    JOIN notes n ON d.note_id = n.id 
    WHERE n.teacher_id = :tid
");
$stmt->execute([':tid' => $teacherId]);
$totalDownloads = (int)$stmt->fetchColumn();

// Recent Notes by this Teacher
$stmt = $db->prepare("
    SELECT n.*, s.name as subject_name, s.code as subject_code,
           (SELECT COUNT(*) FROM downloads d WHERE d.note_id = n.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    WHERE n.teacher_id = :tid
    ORDER BY n.created_at DESC
    LIMIT 5
");
$stmt->execute([':tid' => $teacherId]);
$recentNotes = $stmt->fetchAll();

// Recent Student Downloads on this Teacher's notes
$stmt = $db->prepare("
    SELECT d.downloaded_at, n.title as note_title, u.name as student_name, stu.student_id as student_code, stu.semester as student_semester
    FROM downloads d
    JOIN notes n ON d.note_id = n.id
    JOIN users u ON d.user_id = u.id
    LEFT JOIN students stu ON u.id = stu.user_id
    WHERE n.teacher_id = :tid
    ORDER BY d.downloaded_at DESC
    LIMIT 6
");
$stmt->execute([':tid' => $teacherId]);
$studentDownloads = $stmt->fetchAll();

$pageTitle = "Faculty Dashboard";
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
                <h1 class="page-title">Welcome, <?= e($user['name']) ?> 🎓</h1>
                <p class="page-subtitle"><?= e($teacher['designation'] ?? 'Faculty') ?> &bull; Department of <?= e($teacher['department'] ?? 'Academics') ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>teacher/create-note.php" class="btn btn-success fw-bold shadow-sm">
                    <i class="fas fa-plus-circle me-1"></i> + Upload New Note
                </a>
                <a href="<?= BASE_URL ?>teacher/notes.php" class="btn btn-outline-primary">
                    <i class="fas fa-folder-open me-1"></i> Manage Notes
                </a>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-copy"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalNotes ?></h3>
                        <span class="text-muted small">Total Notes</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $publishedNotes ?></h3>
                        <span class="text-muted small">Published Notes</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon amber">
                        <i class="fas fa-pencil-alt"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $draftNotes ?></h3>
                        <span class="text-muted small">Draft Notes</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-download"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalDownloads ?></h3>
                        <span class="text-muted small">Student Downloads</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Table & Student Downloads Grid -->
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="content-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-file-alt text-primary me-2"></i> Recently Uploaded Notes</h5>
                        <a href="<?= BASE_URL ?>teacher/notes.php" class="small text-decoration-none">View All &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentNotes)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-2 text-secondary"></i>
                                <p class="mb-3">You have not uploaded any notes yet.</p>
                                <a href="<?= BASE_URL ?>teacher/create-note.php" class="btn btn-primary btn-sm">Upload Your First Note</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-custom table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Downloads</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentNotes as $n): ?>
                                            <?php $typeInfo = getFileTypeInfo($n['file_type']); ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-dark text-truncate" style="max-width: 180px;">
                                                        <i class="fas <?= $typeInfo['icon'] ?> <?= $typeInfo['color'] ?> me-1"></i>
                                                        <?= e($n['title']) ?>
                                                    </div>
                                                    <small class="text-muted"><?= formatBytes($n['file_size']) ?> &bull; <?= timeAgo($n['created_at']) ?></small>
                                                </td>
                                                <td><span class="badge bg-light text-dark border"><?= e($n['subject_code']) ?></span></td>
                                                <td>
                                                    <span class="badge <?= $n['status'] === 'published' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                        <?= e($n['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="fw-semibold text-primary"><?= $n['download_count'] ?></td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-1">
                                                        <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Preview">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?= BASE_URL ?>teacher/edit-note.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
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
            </div>

            <!-- Student Access Engagement -->
            <div class="col-lg-5">
                <div class="content-card h-100">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-users text-success me-2"></i> Students Accessing Notes</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($studentDownloads)): ?>
                            <div class="p-5 text-center text-muted">
                                <i class="fas fa-user-graduate fa-2x mb-2 text-secondary"></i>
                                <p class="small mb-0">No downloads recorded for your notes yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($studentDownloads as $d): ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-dark"><?= e($d['student_name']) ?></span>
                                            <span class="badge bg-light text-muted border"><?= timeAgo($d['downloaded_at']) ?></span>
                                        </div>
                                        <div class="small text-muted mb-1">
                                            Downloaded: <span class="fw-semibold text-primary"><?= e($d['note_title']) ?></span>
                                        </div>
                                        <?php if (!empty($d['student_code'])): ?>
                                            <small class="badge bg-secondary-subtle text-secondary"><?= e($d['student_code']) ?> (<?= e($d['student_semester']) ?>)</small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
