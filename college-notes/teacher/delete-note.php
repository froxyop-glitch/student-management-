<?php
/**
 * College Notes Management System
 * Delete Academic Note (Strict Ownership & POST only)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['teacher', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'teacher/notes.php', 'Invalid request method for deletion.', 'danger');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    redirect(BASE_URL . 'teacher/notes.php', 'Security token mismatch. Action canceled.', 'danger');
}

$noteId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$noteId) {
    redirect(BASE_URL . 'teacher/notes.php', 'Invalid note ID specified.', 'danger');
}

$user = currentUser();
$db = getDB();

// Fetch Note
$stmt = $db->prepare("SELECT * FROM notes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $noteId]);
$note = $stmt->fetch();

if (!$note) {
    redirect(BASE_URL . 'teacher/notes.php', 'Note not found.', 'danger');
}

// Ownership check
if ($user['role'] === 'teacher') {
    $tStmt = $db->prepare("SELECT id FROM teachers WHERE user_id = :uid LIMIT 1");
    $tStmt->execute([':uid' => $user['id']]);
    $myTeacherId = (int)$tStmt->fetchColumn();

    if ($note['teacher_id'] != $myTeacherId) {
        redirect(BASE_URL . 'teacher/notes.php', 'Access denied. You can only delete your own notes.', 'danger');
    }
}

// Delete physical file safely
$filePath = realpath(BASE_PATH . $note['file_path']);
if ($filePath && file_exists($filePath)) {
    @unlink($filePath);
}

// Delete database record
$delStmt = $db->prepare("DELETE FROM notes WHERE id = :id");
$delStmt->execute([':id' => $noteId]);

redirect(BASE_URL . 'teacher/notes.php', 'Note "' . $note['title'] . '" was successfully deleted.', 'success');
