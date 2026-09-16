<?php
/**
 * College Notes Management System
 * Admin - Delete Note (POST & CSRF Protected)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/notes/index.php', 'Invalid request method.', 'danger');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    redirect(BASE_URL . 'admin/notes/index.php', 'CSRF validation failed.', 'danger');
}

$noteId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$noteId) {
    redirect(BASE_URL . 'admin/notes/index.php', 'Invalid note ID specified.', 'danger');
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM notes WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $noteId]);
    $note = $stmt->fetch();

    if ($note) {
        // Remove physical file from disk
        $path = realpath(BASE_PATH . $note['file_path']);
        if ($path && file_exists($path)) {
            @unlink($path);
        }

        // Delete note database record (cascades to downloads)
        $del = $db->prepare("DELETE FROM notes WHERE id = :id");
        $del->execute([':id' => $noteId]);

        redirect(BASE_URL . 'admin/notes/index.php', 'Note "' . $note['title'] . '" was removed by administrator.', 'success');
    } else {
        redirect(BASE_URL . 'admin/notes/index.php', 'Note record not found.', 'warning');
    }
} catch (PDOException $e) {
    error_log("Admin delete note error: " . $e->getMessage());
    redirect(BASE_URL . 'admin/notes/index.php', 'Database error removing note: ' . $e->getMessage(), 'danger');
}
