<?php
/**
 * College Notes Management System
 * Public Landing Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch general stats for display
$totalNotes = 0;
$totalStudents = 0;
$totalTeachers = 0;
$totalDownloads = 0;
$recentNotes = [];

try {
    $db = getDB();
    $totalNotes = (int)$db->query("SELECT COUNT(*) FROM notes WHERE status = 'published'")->fetchColumn();
    $totalStudents = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalTeachers = (int)$db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $totalDownloads = (int)$db->query("SELECT COUNT(*) FROM downloads")->fetchColumn();

    $stmt = $db->query("
        SELECT n.*, s.name as subject_name, s.code as subject_code, u.name as teacher_name
        FROM notes n
        JOIN subjects s ON n.subject_id = s.id
        JOIN teachers t ON n.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE n.status = 'published'
        ORDER BY n.created_at DESC
        LIMIT 3
    ");
    $recentNotes = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Landing query error: " . $e->getMessage());
}

$pageTitle = "Welcome to " . APP_NAME;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<header class="hero-section text-white py-5 position-relative" style="background: linear-gradient(135deg, #0d1b2a 0%, #1e3a8a 70%, #172554 100%);">
    <div class="container py-lg-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-warning mb-3 small fw-semibold">
                    <i class="fas fa-shield-alt"></i> Official Academic Resource Repository
                </div>
                <h1 class="display-4 fw-extrabold text-white mb-3">
                    College Notes Management System
                </h1>
                <p class="lead text-light text-opacity-75 mb-4">
                    One secure platform for students, teachers and academic resources. Access verified lecture notes, course materials, and study guides anytime, anywhere.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <?php if (isLoggedIn()): ?>
                        <?php 
                            $targetDash = match(currentRole()) {
                                'admin' => BASE_URL . 'admin/dashboard.php',
                                'teacher' => BASE_URL . 'teacher/dashboard.php',
                                default => BASE_URL . 'user/dashboard.php'
                            };
                        ?>
                        <a href="<?= $targetDash ?>" class="btn btn-warning btn-lg px-4 fw-bold text-dark shadow-sm">
                            <i class="fas fa-th-large me-2"></i> Open My Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-search me-2"></i> Browse Notes
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>user/login.php" class="btn btn-warning btn-lg px-4 fw-bold text-dark shadow-sm">
                            <i class="fas fa-sign-in-alt me-2"></i> Portal Login
                        </a>
                        <a href="<?= BASE_URL ?>user/register.php" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-user-plus me-2"></i> Student Registration
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-white text-dark p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                        <span class="fw-bold fs-5 text-navy"><i class="fas fa-university me-2 text-primary"></i> Academic Pulse</span>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Live Portal</span>
                    </div>
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <h3 class="fw-bold text-primary mb-1"><?= $totalNotes ?></h3>
                                <div class="text-muted small">Verified Notes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <h3 class="fw-bold text-success mb-1"><?= $totalStudents ?></h3>
                                <div class="text-muted small">Enrolled Students</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <h3 class="fw-bold text-warning mb-1"><?= $totalTeachers ?></h3>
                                <div class="text-muted small">Faculty Members</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border">
                                <h3 class="fw-bold text-info mb-1"><?= $totalDownloads ?></h3>
                                <div class="text-muted small">Total Downloads</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-2 border-top text-center">
                        <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-primary w-100 fw-semibold">
                            <i class="fas fa-book-open me-2"></i> Search Academic Notes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Flash message if any -->
<div class="container mt-4">
    <?php displayFlashMessage(); ?>
</div>

<!-- Features Section -->
<section id="features" class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase small tracking-wide">Comprehensive Platform</span>
            <h2 class="display-6 fw-bold mt-1">Built For Modern Higher Education</h2>
            <p class="text-muted">Explore the core features designed to simplify note distribution and student learning.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border p-3 rounded-4 shadow-sm">
                    <div class="card-body">
                        <div class="stat-icon blue mb-3">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <h5 class="fw-bold">Easy Note Access</h5>
                        <p class="text-muted small">
                            Instant access to semester and department notes uploaded directly by approved college professors.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border p-3 rounded-4 shadow-sm">
                    <div class="card-body">
                        <div class="stat-icon green mb-3">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h5 class="fw-bold">Teacher Uploads</h5>
                        <p class="text-muted small">
                            Faculty members can easily publish lecture PDFs, slides, and syllabus notes with full version control.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border p-3 rounded-4 shadow-sm">
                    <div class="card-body">
                        <div class="stat-icon amber mb-3">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="fw-bold">Secure Authentication</h5>
                        <p class="text-muted small">
                            Engineered with Bcrypt password hashing, CSRF defenses, rate-limiting, and role-based permissions.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border p-3 rounded-4 shadow-sm">
                    <div class="card-body">
                        <div class="stat-icon purple mb-3">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h5 class="fw-bold">Subject Organization</h5>
                        <p class="text-muted small">
                            Structured semester-wise and department-wise architecture enables effortless navigation.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border p-3 rounded-4 shadow-sm">
                    <div class="card-body">
                        <div class="stat-icon blue mb-3">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5 class="fw-bold">Fast Search & Filter</h5>
                        <p class="text-muted small">
                            Filter quickly by teacher, semester, subject code, or keyword to retrieve exactly what you need.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border p-3 rounded-4 shadow-sm">
                    <div class="card-body">
                        <div class="stat-icon green mb-3">
                            <i class="fas fa-download"></i>
                        </div>
                        <h5 class="fw-bold">Secure File Downloads</h5>
                        <p class="text-muted small">
                            Validated file stream downloads with full download tracking and built-in PDF viewing.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Uploads Section -->
<?php if (!empty($recentNotes)): ?>
<section class="py-5 bg-light">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="text-primary fw-bold text-uppercase small">Fresh Knowledge</span>
                <h3 class="fw-bold mb-0">Recently Published Notes</h3>
            </div>
            <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-outline-primary btn-sm">
                View All Notes <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
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
                            <p class="note-desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 100, '...')) ?></p>
                            
                            <div class="note-meta d-flex justify-content-between">
                                <span><i class="fas fa-book me-1"></i> <?= e($n['subject_code']) ?></span>
                                <span><i class="fas fa-user me-1"></i> <?= e($n['teacher_name']) ?></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= timeAgo($n['created_at']) ?></small>
                            <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary">
                                View Note
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- About Section -->
<section id="about" class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-primary fw-bold text-uppercase small">About Our Platform</span>
                <h2 class="display-6 fw-bold mt-1 mb-3">Empowering College Education Through Collaborative Learning</h2>
                <p class="text-muted">
                    The College Notes Management System bridges the gap between faculty instruction and student preparation. No more searching across disparate chat groups or losing handwritten notes.
                </p>
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon green" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Faculty-Verified Materials</h6>
                            <p class="text-muted small mb-0">Every note is uploaded by designated college professors and department chairs.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon blue" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Enterprise-Grade Security</h6>
                            <p class="text-muted small mb-0">Safe session management, MIME verification, and automated activity logging.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 p-md-5 rounded-4 bg-navy text-white shadow-lg">
                    <h4 class="fw-bold text-warning mb-3">Academic Role Access</h4>
                    <p class="text-light text-opacity-75 small mb-4">Select your appropriate portal to access tailored tools:</p>
                    <div class="d-grid gap-3">
                        <a href="<?= BASE_URL ?>user/login.php" class="btn btn-outline-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="fas fa-user-graduate me-2 text-primary"></i> Student Portal</div>
                                <small class="text-white-50">Browse notes, download assignments, view subject materials</small>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>user/login.php" class="btn btn-outline-light text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="fas fa-chalkboard-teacher me-2 text-success"></i> Teacher / Faculty Portal</div>
                                <small class="text-white-50">Upload course modules, manage lecture notes, track student engagement</small>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>admin/login.php" class="btn btn-outline-warning text-start p-3 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="fas fa-user-shield me-2 text-warning"></i> College Administrator</div>
                                <small class="text-white-50">Full administrative control, faculty and student CRUD, SQL backups</small>
                            </div>
                            <i class="fas fa-chevron-right text-warning"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
