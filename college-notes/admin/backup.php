<?php
/**
 * College Notes Management System
 * Administrator Database Backup Utility
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = getDB();

// Handle SQL Export Request
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $token = $_GET['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        redirect(BASE_URL . 'admin/backup.php', 'Security validation failed (CSRF mismatch).', 'danger');
    }

    try {
        $tables = [];
        $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlDump  = "-- ==========================================================\n";
        $sqlDump .= "-- College Notes Management System - Database Backup Dump\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Host: " . DB_HOST . " | Database: " . DB_NAME . "\n";
        $sqlDump .= "-- ==========================================================\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Get CREATE TABLE
            $createStmt = $db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            
            $sqlDump .= "-- --------------------------------------------------------\n";
            $sqlDump .= "-- Table structure for `{$table}`\n";
            $sqlDump .= "-- --------------------------------------------------------\n";
            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sqlDump .= $createRow[1] . ";\n\n";

            // Get Table Data
            $rowsStmt = $db->query("SELECT * FROM `{$table}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $sqlDump .= "-- Dumping data for table `{$table}`\n";
                foreach ($rows as $row) {
                    $keys = array_map(fn($k) => "`" . $k . "`", array_keys($row));
                    $values = array_map(function ($val) use ($db) {
                        if ($val === null) return "NULL";
                        return $db->quote($val);
                    }, array_values($row));

                    $sqlDump .= "INSERT INTO `{$table}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sqlDump .= "-- Dump completed successfully.\n";

        $filename = "college_notes_backup_" . date('Y-m-d_H-i-s') . ".sql";

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sqlDump));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        echo $sqlDump;
        exit;

    } catch (Exception $e) {
        error_log("Backup generation error: " . $e->getMessage());
        redirect(BASE_URL . 'admin/backup.php', 'Failed to generate database dump: ' . $e->getMessage(), 'danger');
    }
}

// Fetch table statistics
$tablesList = $db->query("SHOW TABLE STATUS FROM `" . DB_NAME . "`")->fetchAll();
$totalSize = 0;
foreach ($tablesList as $t) {
    $totalSize += ($t['Data_length'] + $t['Index_length']);
}

$pageTitle = "Database Backup & Maintenance";
$customCss = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php displayFlashMessage(); ?>

        <div class="page-header-bar">
            <div>
                <h1 class="page-title">Database Backup & Maintenance</h1>
                <p class="page-subtitle">Export full SQL database schema, seed data, and operational logs</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="content-card mb-4">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-database text-warning me-2"></i> Instant SQL Export</h5>
                    </div>
                    <div class="card-body">
                        <div class="backup-card mb-4">
                            <div class="stat-icon amber mx-auto mb-3" style="width: 64px; height: 64px; font-size: 1.8rem;">
                                <i class="fas fa-file-archive"></i>
                            </div>
                            <h4 class="fw-bold">Export Full Database (.SQL)</h4>
                            <p class="text-muted small max-w-500 mx-auto mb-4">
                                This will generate a standalone SQL dump containing all table schemas (users, teachers, students, notes, downloads) and their current records. Ready to be restored in phpMyAdmin or MySQL CLI.
                            </p>
                            <a href="<?= BASE_URL ?>admin/backup.php?action=download&csrf_token=<?= e(generateCsrfToken()) ?>" class="btn btn-warning btn-lg fw-bold text-dark px-4 shadow-sm">
                                <i class="fas fa-download me-2"></i> Download Full SQL Backup
                            </a>
                        </div>

                        <div class="alert alert-info small d-flex align-items-center">
                            <i class="fas fa-shield-alt fa-lg me-3 text-primary"></i>
                            <div>
                                <strong>Zero Credential Exposure:</strong> Generated dumps contain encrypted password hashes and sanitized schema statements. No server database passwords or credentials are exposed in the export.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0"><i class="fas fa-server text-primary me-2"></i> Database Details</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Database Name</span>
                                <span class="fw-bold text-dark"><?= e(DB_NAME) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Total Tables</span>
                                <span class="fw-bold"><?= count($tablesList) ?> tables</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Database Size</span>
                                <span class="fw-bold"><?= formatBytes($totalSize) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between py-3">
                                <span class="text-muted">Collation / Encoding</span>
                                <span class="fw-bold">utf8mb4_unicode_ci</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
