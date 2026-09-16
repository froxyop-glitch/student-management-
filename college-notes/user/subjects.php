<?php
/**
 * College Notes Management System
 * Student Subjects Directory
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['student', 'teacher', 'admin']);

$db = getDB();
$deptFilter = sanitize($_GET['department'] ?? '');
$semFilter = sanitize($_GET['semester'] ?? '');

$where = ["1=1"];
$params = [];
if (!empty($deptFilter)) {
    $where[] = "s.department = :dept";
    $params[':dept'] = $deptFilter;
}
if (!empty($semFilter)) {
    $where[] = "s.semester = :sem";
    $params[':sem'] = $semFilter;
}
$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.*, COUNT(n.id) as notes_count
    FROM subjects s
    LEFT JOIN notes n ON s.id = n.subject_id AND n.status = 'published'
    WHERE $whereSql
    GROUP BY s.id
    ORDER BY s.semester ASC, s.name ASC
");
$stmt->execute($params);
$subjects = $stmt->fetchAll();

$allDepts = $db->query("SELECT DISTINCT department FROM subjects ORDER BY department ASC")->fetchAll();
$allSems = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();

$pageTitle = "Curriculum Subjects";
$customCss = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Curriculum Subjects</h1>
                <p class="page-subtitle">Browse academic subjects and their associated study materials</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card">
            <form action="<?= BASE_URL ?>user/subjects.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Department</label>
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($allDepts as $d): ?>
                            <option value="<?= e($d['department']) ?>" <?= $deptFilter === $d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">All Semesters</option>
                        <?php foreach ($allSems as $s): ?>
                            <option value="<?= e($s['semester_name']) ?>" <?= $semFilter === $s['semester_name'] ? 'selected' : '' ?>><?= e($s['semester_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="<?= BASE_URL ?>user/subjects.php" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <!-- Subjects Grid -->
        <div class="row g-4">
            <?php if (empty($subjects)): ?>
                <div class="col-12">
                    <div class="card p-5 text-center text-muted">
                        <i class="fas fa-book-open fa-3x mb-2"></i>
                        <h5>No subjects found matching your criteria</h5>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($subjects as $sub): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-secondary"><?= e($sub['code']) ?></span>
                                    <span class="badge bg-light text-dark border"><?= e($sub['semester']) ?></span>
                                </div>
                                <h5 class="fw-bold text-navy mb-2"><?= e($sub['name']) ?></h5>
                                <p class="text-muted small mb-3"><i class="fas fa-building me-1"></i> <?= e($sub['department']) ?></p>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="small fw-bold text-primary"><?= $sub['notes_count'] ?> Notes Available</span>
                                    <a href="<?= BASE_URL ?>user/notes.php?subject=<?= $sub['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        View Notes &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
