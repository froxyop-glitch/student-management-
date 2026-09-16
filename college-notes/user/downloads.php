<?php
/**
 * College Notes Management System
 * Student Download History
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = currentUser();
$db = getDB();

$stmt = $db->prepare("
    SELECT d.id as download_id, d.downloaded_at, 
           n.id as note_id, n.title, n.file_name, n.file_type, n.file_size,
           s.name as subject_name, s.code as subject_code,
           u.name as teacher_name
    FROM downloads d
    JOIN notes n ON d.note_id = n.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN teachers t ON n.teacher_id = t.id
    JOIN users u ON t.user_id = u.id
    WHERE d.user_id = :uid
    ORDER BY d.downloaded_at DESC
");
$stmt->execute([':uid' => $user['id']]);
$downloads = $stmt->fetchAll();

$pageTitle = "My Downloads History";
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
                <h1 class="page-title">My Download History</h1>
                <p class="page-subtitle">Log of all study materials and notes downloaded by your account</p>
            </div>
            <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-primary">
                <i class="fas fa-search me-1"></i> Browse More Notes
            </a>
        </div>

        <div class="content-card">
            <div class="card-body p-0">
                <?php if (empty($downloads)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-cloud-download-alt fa-3x mb-3 text-secondary"></i>
                        <h5 class="fw-bold">No downloads yet</h5>
                        <p class="small mb-3">You haven't downloaded any notes yet. Browse the catalog to start learning!</p>
                        <a href="<?= BASE_URL ?>user/notes.php" class="btn btn-outline-primary btn-sm">Explore Notes</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Note Title</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>File Size</th>
                                    <th>Downloaded On</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($downloads as $row): ?>
                                    <?php $typeInfo = getFileTypeInfo($row['file_type']); ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas <?= $typeInfo['icon'] ?> <?= $typeInfo['color'] ?> fa-lg"></i>
                                                <div>
                                                    <a href="<?= BASE_URL ?>user/note-view.php?id=<?= $row['note_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                        <?= e($row['title']) ?>
                                                    </a>
                                                    <div class="small text-muted"><?= e($row['file_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary me-1"><?= e($row['subject_code']) ?></span>
                                            <?= e($row['subject_name']) ?>
                                        </td>
                                        <td><?= e($row['teacher_name']) ?></td>
                                        <td><?= formatBytes($row['file_size']) ?></td>
                                        <td class="text-muted small">
                                            <?= date('M j, Y - g:i A', strtotime($row['downloaded_at'])) ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= BASE_URL ?>user/note-download.php?id=<?= $row['note_id'] ?>" class="btn btn-sm btn-primary" title="Re-download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
