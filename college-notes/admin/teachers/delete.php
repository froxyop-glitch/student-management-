<?php
/**
 * College Notes Management System
 * Admin - Delete Teacher (POST & CSRF Protected)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/teachers/index.php', 'Invalid request method.', 'danger');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    redirect(BASE_URL . 'admin/teachers/index.php', 'CSRF verification failed.', 'danger');
}

$teacherId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$teacherId) {
    redirect(BASE_URL . 'admin/teachers/index.php', 'Invalid teacher ID.', 'danger');
}

$db = getDB();

try {
    // Fetch associated user_id
    $stmt = $db->prepare("SELECT user_id FROM teachers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $teacherId]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        // Find notes to remove files from disk
        $nStmt = $db->prepare("SELECT file_path FROM notes WHERE teacher_id = :tid");
        $nStmt->execute([':tid' => $teacherId]);
        while ($row = $nStmt->fetch()) {
            $path = realpath(BASE_PATH . $row['file_path']);
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }

        // Deleting user cascades to teachers, notes, downloads
        $del = $db->prepare("DELETE FROM users WHERE id = :uid");
        $del->execute([':uid' => $userId]);

        redirect(BASE_URL . 'admin/teachers/index.php', 'Faculty member and associated resources removed.', 'success');
    } else {
        redirect(BASE_URL . 'admin/teachers/index.php', 'Faculty member record not found.', 'warning');
    }
} catch (PDOException $e) {
    error_log("Delete teacher error: " . $e->getMessage());
    redirect(BASE_URL . 'admin/teachers/index.php', 'Database error removing faculty: ' . $e->getMessage(), 'danger');
}
