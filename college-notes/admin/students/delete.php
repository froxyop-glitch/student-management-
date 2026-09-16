<?php
/**
 * College Notes Management System
 * Admin - Delete Student (POST & CSRF Protected)
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/students/index.php', 'Invalid request method.', 'danger');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    redirect(BASE_URL . 'admin/students/index.php', 'CSRF verification failed.', 'danger');
}

$studentId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$studentId) {
    redirect(BASE_URL . 'admin/students/index.php', 'Invalid student ID.', 'danger');
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT user_id FROM students WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $studentId]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $del = $db->prepare("DELETE FROM users WHERE id = :uid");
        $del->execute([':uid' => $userId]);

        redirect(BASE_URL . 'admin/students/index.php', 'Student removed from system.', 'success');
    } else {
        redirect(BASE_URL . 'admin/students/index.php', 'Student record not found.', 'warning');
    }
} catch (PDOException $e) {
    error_log("Delete student error: " . $e->getMessage());
    redirect(BASE_URL . 'admin/students/index.php', 'Database error removing student: ' . $e->getMessage(), 'danger');
}
