<?php
/**
 * College Notes Management System
 * Student Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['student', 'admin']);

$user = currentUser();
$db = getDB();

// 1. Fetch Student Details
$studentDetails = null;
$stmt = $db->prepare("SELECT * FROM students WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
$studentDetails = $stmt->fetch();

$currentSemester = $studentDetails['semester'] ?? 'All Semesters';
$currentDept = $studentDetails['department'] ?? 'All Departments';

// 2. Fetch Dashboard Statistics
$totalNotesCount = (int)$db->query("SELECT COUNT(*) FROM notes WHERE status = 'published'")->fetchColumn();
$totalSubjectsCount = (int)$db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();

$myDownloadsCount = 0;
$stmt = $db->prepare("SELECT COUNT(*) FROM downloads WHERE user_id = :uid");
$stmt->execute([':uid' => $user['id']]);
$myDownloadsCount = (int)$stmt->fetchColumn();

// 3. Fetch Recent Notes
$recentNotes = [];
$stmt = $db->prepare("
    SELECT n.*, s.name as subject_name, s.code as subject_code, u.name as teacher_name,
           (SELECT COUNT(*) FROM downloads d WHERE d.note_id = n.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE n.status = 'published'
    ORDER BY n.created_at DESC
    LIMIT 6
");
$stmt->execute();
$recentNotes = $stmt->fetchAll();

// 4. Fetch Popular Notes (Top downloaded)
$popularNotes = [];
$stmt = $db->prepare("
    SELECT n.*, s.name as subject_name, s.code as subject_code, u.name as teacher_name,
           COUNT(d.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN downloads d ON n.id = d.note_id
    WHERE n.status = 'published'
    GROUP BY n.id
    ORDER BY download_count DESC, n.created_at DESC
    LIMIT 4
");
$stmt->execute();
$popularNotes = $stmt->fetchAll();

// 5. Fetch Subjects for Current Semester
$relevantSubjects = [];
$stmt = $db->prepare("
    SELECT s.*, (SELECT COUNT(*) FROM notes n WHERE n.subject_id = s.id AND n.status = 'published') as notes_count
    FROM subjects s
    WHERE s.department = :dept OR s.semester = :sem
    ORDER BY s.semester ASC, s.name ASC
    LIMIT 6
");
$stmt->execute([':dept' => $currentDept, ':sem' => $currentSemester]);
$relevantSubjects = $stmt->fetchAll();
if (empty($relevantSubjects)) {
    // Fallback if no matching department
    $relevantSubjects = $db->query("SELECT s.*, (SELECT COUNT(*) FROM notes n WHERE n.subject_id = s.id AND n.status = 'published') as notes_count FROM subjects s LIMIT 6")->fetchAll();
}

$pageTitle = "Student Dashboard";
$customCss = ['dashboard.css'];
$customJs = ['dashboard.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php displayFlashMessage(); ?>

        <!-- Welcome Banner -->
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Welcome back, <?= e($user['name']) ?>! 👋</h1>
                <p class="page-subtitle">Here is what is happening in your academic curriculum today.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i> Find Notes
                </a>
            </div>
        </div>

        <!-- 4 Stat Metric Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalNotesCount ?></h3>
                        <span class="text-muted small">Total Notes Available</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-shapes"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalSubjectsCount ?></h3>
                        <span class="text-muted small">Available Subjects</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon amber">
                        <i class="fas fa-download"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $myDownloadsCount ?></h3>
                        <span class="text-muted small">My Downloads</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0"><?= e($currentSemester) ?></h5>
                        <span class="text-muted small"><?= e($currentDept) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white p-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-3 mb-lg-0">
                    <h4 class="fw-bold mb-1 text-white">Need materials for your upcoming exams?</h4>
                    <p class="text-white-50 mb-0 small">Search through verified lecture slides, modules, solved assignments and question banks.</p>
                </div>
                <div class="col-lg-5">
                    <form action="<?= BASE_URL ?>user/notes.php" method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by topic, subject code..." required>
                        <button type="submit" class="btn btn-warning fw-bold text-dark px-4 flex-shrink-0">
                            Search
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Notes Section -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0"><i class="fas fa-clock text-primary me-2"></i> Recent Notes</h4>
            <a href="<?= BASE_URL ?>user/notes.php" class="small text-decoration-none fw-semibold">View All Notes &rarr;</a>
        </div>

        <div class="row g-4 mb-5">
            <?php if (empty($recentNotes)): ?>
                <div class="col-12">
                    <div class="card border p-4 text-center text-muted">
                        <i class="fas fa-folder-open fa-3x mb-2"></i>
                        <p class="mb-0">No notes have been published yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recentNotes as $n): ?>
                    <?php $typeInfo = getFileTypeInfo($n['file_type']); ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="note-card">
                            <span class="badge <?= $typeInfo['badge'] ?> note-badge text-uppercase"><?= e($n['file_type']) ?></span>
                            <div class="card-body">
                                <div class="note-icon-large <?= $typeInfo['color'] ?>">
                                    <i class="fas <?= $typeInfo['icon'] ?>"></i>
                                </div>
                                <h5 class="note-title"><?= e($n['title']) ?></h5>
                                <p class="note-desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 90, '...')) ?></p>
                                <div class="note-meta d-flex justify-content-between">
                                    <span><i class="fas fa-book me-1"></i> <?= e($n['subject_code']) ?></span>
                                    <span><i class="fas fa-chalkboard-teacher me-1"></i> <?= e($n['teacher_name']) ?></span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted"><i class="fas fa-download me-1"></i> <?= $n['download_count'] ?> downloads</small>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary" title="View details">
                                        View
                                    </a>
                                    <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary" title="Download note">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <!-- Popular Notes List -->
            <div class="col-lg-7">
                <div class="content-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-fire text-danger me-2"></i> Most Downloaded Notes</h5>
                        <a href="<?= BASE_URL ?>user/notes.php" class="small text-decoration-none">Explore &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php if (empty($popularNotes)): ?>
                                <div class="p-4 text-center text-muted">No downloads recorded yet.</div>
                            <?php else: ?>
                                <?php foreach ($popularNotes as $p): ?>
                                    <?php $typeInfo = getFileTypeInfo($p['file_type']); ?>
                                    <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon <?= $p['file_type'] === 'pdf' ? 'red' : 'blue' ?>" style="width: 44px; height: 44px; font-size: 1.2rem;">
                                                <i class="fas <?= $typeInfo['icon'] ?>"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">
                                                    <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $p['id'] ?>" class="text-dark text-decoration-none">
                                                        <?= e($p['title']) ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted"><?= e($p['subject_name']) ?> &bull; <?= e($p['teacher_name']) ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-light text-dark border mb-1 d-block"><?= $p['download_count'] ?> downloads</span>
                                            <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-download"></i> Get
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Modules List -->
            <div class="col-lg-5">
                <div class="content-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-layer-group text-primary me-2"></i> Core Subjects</h5>
                        <a href="<?= BASE_URL ?>user/subjects.php" class="small text-decoration-none">All Subjects</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($relevantSubjects as $sub): ?>
                                <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($sub['name']) ?></div>
                                        <small class="text-muted"><span class="badge bg-secondary me-1"><?= e($sub['code']) ?></span> <?= e($sub['semester']) ?></small>
                                    </div>
                                    <a href="<?= BASE_URL ?>user/notes.php?subject=<?= $sub['id'] ?>" class="badge bg-primary rounded-pill text-decoration-none px-3 py-2">
                                        <?= $sub['notes_count'] ?> Notes
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
