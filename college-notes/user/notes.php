<?php
/**
 * College Notes Management System
 * Browse, Search & Filter Notes with Pagination
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

// 1. Capture Filters
$search     = sanitize($_GET['search'] ?? '');
$subjectId  = filter_var($_GET['subject'] ?? null, FILTER_VALIDATE_INT);
$semester   = sanitize($_GET['semester'] ?? '');
$department = sanitize($_GET['department'] ?? '');
$teacherId  = filter_var($_GET['teacher'] ?? null, FILTER_VALIDATE_INT);
$fileType   = sanitize($_GET['file_type'] ?? '');
$page       = max((int)($_GET['page'] ?? 1), 1);
$perPage    = 9;
$offset     = ($page - 1) * $perPage;

// 2. Build Safe Dynamic Query
$where = ["n.status = 'published'"];
$params = [];

if (!empty($search)) {
    $where[] = "(n.title LIKE :search OR n.description LIKE :search OR s.name LIKE :search OR s.code LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if (!empty($subjectId)) {
    $where[] = "n.subject_id = :subject_id";
    $params[':subject_id'] = $subjectId;
}
if (!empty($semester)) {
    $where[] = "n.semester = :semester";
    $params[':semester'] = $semester;
}
if (!empty($department)) {
    $where[] = "n.department = :department";
    $params[':department'] = $department;
}
if (!empty($teacherId)) {
    $where[] = "n.teacher_id = :teacher_id";
    $params[':teacher_id'] = $teacherId;
}
if (!empty($fileType)) {
    $where[] = "n.file_type = :file_type";
    $params[':file_type'] = $fileType;
}

$whereClause = implode(' AND ', $where);

// 3. Count Total Matching Notes
$countSql = "
    SELECT COUNT(*) 
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    WHERE $whereClause
";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$totalNotes = (int)$stmtCount->fetchColumn();
$totalPages = max((int)ceil($totalNotes / $perPage), 1);

// 4. Fetch Paginated Notes
$sql = "
    SELECT n.*, s.name as subject_name, s.code as subject_code, u.name as teacher_name,
           (SELECT COUNT(*) FROM downloads d WHERE d.note_id = n.id) as download_count
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE $whereClause
    ORDER BY n.created_at DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$notes = $stmt->fetchAll();

// 5. Fetch Filter Options
$allSubjects    = $db->query("SELECT id, name, code FROM subjects ORDER BY name ASC")->fetchAll();
$allSemesters   = $db->query("SELECT semester_name FROM semesters ORDER BY semester_number ASC")->fetchAll();
$allDepartments = $db->query("SELECT DISTINCT department FROM subjects ORDER BY department ASC")->fetchAll();
$allTeachers    = $db->query("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY u.name ASC")->fetchAll();

$pageTitle = "Browse Academic Notes";
$customCss = ['dashboard.css'];
$customJs = ['dashboard.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="<?= isLoggedIn() ? 'dashboard-layout' : 'container py-4' ?>">
    <?php if (isLoggedIn()): ?>
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php endif; ?>

    <main class="<?= isLoggedIn() ? 'main-content' : '' ?>">
        <?php displayFlashMessage(); ?>

        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Explore Academic Notes</h1>
                <p class="page-subtitle">Showing <?= $totalNotes ?> curated materials uploaded by academic faculty</p>
            </div>
            <?php if (currentRole() === 'teacher'): ?>
                <a href="<?= BASE_URL ?>teacher/create-note.php" class="btn btn-success">
                    <i class="fas fa-cloud-upload-alt me-1"></i> Upload New Note
                </a>
            <?php endif; ?>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="filter-card">
            <form action="<?= BASE_URL ?>user/notes.php" method="GET" class="row g-3 align-items-end">
                <!-- Search Keyword -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-semibold text-muted">Search Keyword</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search notes..." value="<?= e($search) ?>">
                    </div>
                </div>

                <!-- Subject Dropdown -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Subject</label>
                    <select name="subject" class="form-select auto-submit-filter">
                        <option value="">All Subjects</option>
                        <?php foreach ($allSubjects as $sub): ?>
                            <option value="<?= $sub['id'] ?>" <?= $subjectId == $sub['id'] ? 'selected' : '' ?>>
                                <?= e($sub['code']) ?> - <?= e($sub['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester Dropdown -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Semester</label>
                    <select name="semester" class="form-select auto-submit-filter">
                        <option value="">All Semesters</option>
                        <?php foreach ($allSemesters as $sem): ?>
                            <option value="<?= e($sem['semester_name']) ?>" <?= $semester === $sem['semester_name'] ? 'selected' : '' ?>>
                                <?= e($sem['semester_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Department Dropdown -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Department</label>
                    <select name="department" class="form-select auto-submit-filter">
                        <option value="">All Departments</option>
                        <?php foreach ($allDepartments as $dept): ?>
                            <option value="<?= e($dept['department']) ?>" <?= $department === $dept['department'] ? 'selected' : '' ?>>
                                <?= e($dept['department']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Teacher Dropdown -->
                <div class="col-lg-2 col-md-4">
                    <label class="form-label small fw-semibold text-muted">Faculty</label>
                    <select name="teacher" class="form-select auto-submit-filter">
                        <option value="">All Faculty</option>
                        <?php foreach ($allTeachers as $tch): ?>
                            <option value="<?= $tch['id'] ?>" <?= $teacherId == $tch['id'] ? 'selected' : '' ?>>
                                <?= e($tch['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="col-lg-1 col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100" title="Apply Filter">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Notes Card Grid -->
        <?php if (empty($notes)): ?>
            <div class="card border-0 shadow-sm p-5 text-center text-muted rounded-4 bg-white">
                <i class="fas fa-search-minus fa-3x mb-3 text-secondary"></i>
                <h4 class="fw-bold text-dark">No Matching Notes Found</h4>
                <p class="mb-3">Try adjusting your search criteria or clear active filters.</p>
                <div>
                    <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-outline-primary btn-sm">Clear All Filters</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <?php foreach ($notes as $n): ?>
                    <?php $typeInfo = getFileTypeInfo($n['file_type']); ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="note-card">
                            <span class="badge <?= $typeInfo['badge'] ?> note-badge text-uppercase"><?= e($n['file_type']) ?></span>
                            <div class="card-body">
                                <div class="note-icon-large <?= $typeInfo['color'] ?>">
                                    <i class="fas <?= $typeInfo['icon'] ?>"></i>
                                </div>
                                <h5 class="note-title"><?= e($n['title']) ?></h5>
                                <p class="note-desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 110, '...')) ?></p>
                                
                                <div class="note-meta">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><i class="fas fa-book me-1 text-primary"></i> <?= e($n['subject_name']) ?></span>
                                        <span class="fw-semibold text-secondary"><?= e($n['semester']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted">
                                        <span><i class="fas fa-user me-1"></i> <?= e($n['teacher_name']) ?></span>
                                        <span><i class="fas fa-save me-1"></i> <?= formatBytes($n['file_size']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted"><i class="fas fa-download me-1"></i> <?= $n['download_count'] ?> downloads</small>
                                <div class="d-flex gap-2">
                                    <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Bar -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Notes pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php 
                            // Preserve existing query params
                            $queryParams = $_GET;
                            $buildPageUrl = function($pageNum) use ($queryParams) {
                                $queryParams['page'] = $pageNum;
                                return '?' . http_build_query($queryParams);
                            };
                        ?>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $buildPageUrl($page - 1) ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $buildPageUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $buildPageUrl($page + 1) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
