<?php
/**
 * College Notes Management System
 * Secure Note Download Handler & Audit Tracker
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is authenticated before downloading
requireLogin();

$noteId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$noteId) {
    redirect(BASE_URL . 'user/notes.php', 'Invalid note specified for download.', 'danger');
}

$user = currentUser();
$db = getDB();

$stmt = $db->prepare("SELECT * FROM notes WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $noteId]);
$note = $stmt->fetch();

if (!$note) {
    redirect(BASE_URL . 'user/notes.php', 'Note not found.', 'danger');
}

// Check authorization if unpublished
if ($note['status'] !== 'published') {
    $role = currentRole();
    $canDownload = ($role === 'admin') || ($role === 'teacher' && ($user['profile_id'] ?? null) == $note['teacher_id']);
    if (!$canDownload) {
        redirect(BASE_URL . 'user/notes.php', 'This file is not currently accessible for download.', 'warning');
    }
}

// Resolve real filesystem path and prevent directory traversal
$resolvedPath = realpath(BASE_PATH . $note['file_path']);
$expectedUploadDir = realpath(UPLOAD_DIR);

if ($resolvedPath === false || !file_exists($resolvedPath)) {
    // If not found in primary path, try direct upload dir with stored file name
    $fallbackPath = realpath(UPLOAD_DIR . basename($note['file_path']));
    if ($fallbackPath !== false && file_exists($fallbackPath)) {
        $resolvedPath = $fallbackPath;
    } else {
        redirect(BASE_URL . 'user/note-view.php?id=' . $noteId, 'The physical file could not be located on the server.', 'danger');
    }
}

// Directory Traversal Guard: Ensure file is inside allowed directory
if (strpos($resolvedPath, $expectedUploadDir) !== 0 && strpos($resolvedPath, realpath(BASE_PATH . 'assets/uploads')) !== 0) {
    http_response_code(403);
    exit('Access to file location forbidden.');
}

// Record Download in Audit Table
try {
    $logStmt = $db->prepare("
        INSERT INTO downloads (note_id, user_id, ip_address, user_agent, downloaded_at)
        VALUES (:note_id, :user_id, :ip, :ua, NOW())
    ");
    $logStmt->execute([
        ':note_id' => $note['id'],
        ':user_id' => $user['id'],
        ':ip'      => getClientIp(),
        ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 250)
    ]);
} catch (PDOException $e) {
    error_log("Failed to log download record: " . $e->getMessage());
}

// Send Secure Download Headers
$downloadName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $note['file_name']);
if (empty($downloadName)) {
    $downloadName = 'note_' . $note['id'] . '.' . $note['file_type'];
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($resolvedPath));

// Clean any previous output buffer
if (ob_get_level()) {
    ob_end_clean();
}

readfile($resolvedPath);
exit;
