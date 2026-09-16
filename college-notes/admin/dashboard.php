<?php
/**
 * College Notes Management System
 * Administrator Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();

// 1. High-level Statistics
$totalStudents = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = (int)$db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalNotes    = (int)$db->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$totalDownloads= (int)$db->query("SELECT COUNT(*) FROM downloads")->fetchColumn();

// 2. Breakdown counts
$publishedNotes = (int)$db->query("SELECT COUNT(*) FROM notes WHERE status = 'published'")->fetchColumn();
$draftNotes     = (int)$db->query("SELECT COUNT(*) FROM notes WHERE status = 'draft'")->fetchColumn();
$activeUsers    = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

// 3. Recent Notes for Moderation
$stmt = $db->query("
    SELECT n.*, s.name as subject_name, s.code as subject_code, u.name as teacher_name
    FROM notes n
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 5
");
$recentNotes = $stmt->fetchAll();

// 4. Recent System Login Audits
$recentLogs = $db->query("
    SELECT * FROM login_logs 
    ORDER BY attempted_at DESC 
    LIMIT 6
")->fetchAll();

$pageTitle = "System Administration";
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php displayFlashMessage(); ?>

        <div class="admin-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-warning text-dark fw-bold mb-2">Central IT Control</span>
                <h1 class="h3 fw-bold mb-1 text-white">College Portal Administration</h1>
                <p class="text-white-50 small mb-0">System health, academic staff management, and notes moderation</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>admin/backup.php" class="btn btn-warning fw-bold text-dark shadow-sm">
                    <i class="fas fa-database me-1"></i> Download SQL Backup
                </a>
            </div>
        </div>

        <!-- 4 Primary Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalStudents ?></h3>
                        <span class="text-muted small">Total Students</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalTeachers ?></h3>
                        <span class="text-muted small">Faculty Members</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon amber">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalNotes ?></h3>
                        <span class="text-muted small">Total Notes (<?= $publishedNotes ?> Live)</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-cloud-download-alt"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= $totalDownloads ?></h3>
                        <span class="text-muted small">Total Downloads</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Administration Actions -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-bolt text-warning me-2"></i> Quick Administrative Actions</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>admin/teachers/create.php" class="btn btn-outline-success w-100 py-3 text-start d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">+ Add New Teacher</div>
                            <small class="text-muted">Register verified faculty</small>
                        </div>
                        <i class="fas fa-user-tie fa-lg text-success"></i>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>admin/students/create.php" class="btn btn-outline-primary w-100 py-3 text-start d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">+ Enroll Student</div>
                            <small class="text-muted">Create student account</small>
                        </div>
                        <i class="fas fa-user-graduate fa-lg text-primary"></i>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>admin/notes/index.php" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center justify-content-between">
                        <div>
                            <div class="fw-bold">Moderate Notes</div>
                            <small class="text-muted">Review & manage materials</small>
                        </div>
                        <i class="fas fa-file-contract fa-lg text-secondary"></i>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="<?= BASE_URL ?>admin/backup.php" class="btn btn-outline-warning w-100 py-3 text-start d-flex align-items-center justify-content-between text-dark">
                        <div>
                            <div class="fw-bold">Backup Database</div>
                            <small class="text-muted">Export clean MySQL dump</small>
                        </div>
                        <i class="fas fa-database fa-lg text-warning"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Notes for Moderation -->
            <div class="col-lg-7">
                <div class="content-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-clock text-primary me-2"></i> Recent Notes Uploaded</h5>
                        <a href="<?= BASE_URL ?>admin/notes/index.php" class="small text-decoration-none">All Notes &rarr;</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-custom table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Teacher</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentNotes as $n): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 200px;"><?= e($n['title']) ?></div>
                                                <small class="text-muted"><?= e($n['subject_code']) ?> &bull; <?= e($n['semester']) ?></small>
                                            </td>
                                            <td><?= e($n['teacher_name']) ?></td>
                                            <td>
                                                <span class="badge <?= $n['status'] === 'published' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= e($n['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>admin/notes/edit.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Audit Trail -->
            <div class="col-lg-5">
                <div class="content-card h-100">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-shield-alt text-danger me-2"></i> Security & Login Audit</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush small">
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <div class="fw-bold"><?= e($log['email']) ?></div>
                                        <div class="text-muted"><i class="fas fa-laptop me-1"></i> <?= e($log['ip_address']) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge <?= $log['status'] === 'success' ? 'bg-success' : 'bg-danger' ?> mb-1">
                                            <?= e($log['status']) ?>
                                        </span>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= timeAgo($log['attempted_at']) ?></div>
                                    </div>
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
