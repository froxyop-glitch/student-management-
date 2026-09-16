<?php
/**
 * College Notes Management System
 * Unified Registration Page (Student & Teacher Tabs)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . 'user/dashboard.php');
}

$error = '';
$activeTab = $_POST['reg_type'] ?? 'student';

// Fetch available semesters for student dropdown
$db = getDB();
$semesters = [];
try {
    $semesters = $db->query("SELECT * FROM semesters ORDER BY semester_number ASC")->fetchAll();
} catch (Exception $e) {
    $semesters = [];
}

// Process Registration Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $regType   = $_POST['reg_type'] ?? 'student';
    $name      = sanitize($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $dept      = sanitize($_POST['department'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Verify CSRF
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Security validation token mismatch. Please try submitting again.';
    } elseif (empty($name) || empty($email) || empty($dept) || empty($password)) {
        $error = 'Please fill in all mandatory registration fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid academic email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'The passwords entered do not match.';
    } else {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email address already exists. Please login instead.';
            } else {
                // Handle Role Specific Validations
                if ($regType === 'student') {
                    $studentId = sanitize($_POST['student_id'] ?? '');
                    $semester  = sanitize($_POST['semester'] ?? '');
                    $batch     = sanitize($_POST['batch'] ?? '');

                    if (empty($studentId) || empty($semester) || empty($batch)) {
                        $error = 'Please provide your Student ID, Semester, and Batch.';
                    } else {
                        // Check if student_id is unique
                        $check = $db->prepare("SELECT id FROM students WHERE student_id = :sid");
                        $check->execute([':sid' => $studentId]);
                        if ($check->fetch()) {
                            $error = 'This Student ID is already registered.';
                        } else {
                            // Transaction: Insert user + student
                            $db->beginTransaction();
                            $pwHash = password_hash($password, PASSWORD_DEFAULT);
                            $uStmt = $db->prepare("
                                INSERT INTO users (name, email, password, role, status, created_at)
                                VALUES (:name, :email, :password, 'student', 'active', NOW())
                            ");
                            $uStmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':password' => $pwHash
                            ]);
                            $newUserId = (int)$db->lastInsertId();

                            $sStmt = $db->prepare("
                                INSERT INTO students (user_id, student_id, semester, department, batch, phone, created_at)
                                VALUES (:uid, :sid, :sem, :dept, :batch, :phone, NOW())
                            ");
                            $sStmt->execute([
                                ':uid'   => $newUserId,
                                ':sid'   => $studentId,
                                ':sem'   => $semester,
                                ':dept'  => $dept,
                                ':batch' => $batch,
                                ':phone' => $phone
                            ]);
                            $db->commit();

                            redirect(BASE_URL . 'user/login.php', 'Registration successful! You can now log in with your credentials.', 'success');
                        }
                    }
                } elseif ($regType === 'teacher') {
                    $employeeId  = sanitize($_POST['employee_id'] ?? '');
                    $designation = sanitize($_POST['designation'] ?? '');

                    if (empty($employeeId) || empty($designation)) {
                        $error = 'Please provide your Faculty Employee ID and Designation.';
                    } else {
                        // Check if employee_id is unique
                        $check = $db->prepare("SELECT id FROM teachers WHERE employee_id = :eid");
                        $check->execute([':eid' => $employeeId]);
                        if ($check->fetch()) {
                            $error = 'This Employee ID is already registered.';
                        } else {
                            // Transaction: Insert user + teacher
                            $db->beginTransaction();
                            $pwHash = password_hash($password, PASSWORD_DEFAULT);
                            $uStmt = $db->prepare("
                                INSERT INTO users (name, email, password, role, status, created_at)
                                VALUES (:name, :email, :password, 'teacher', 'active', NOW())
                            ");
                            $uStmt->execute([
                                ':name' => $name,
                                ':email' => $email,
                                ':password' => $pwHash
                            ]);
                            $newUserId = (int)$db->lastInsertId();

                            $tStmt = $db->prepare("
                                INSERT INTO teachers (user_id, employee_id, department, designation, phone, created_at)
                                VALUES (:uid, :eid, :dept, :desig, :phone, NOW())
                            ");
                            $tStmt->execute([
                                ':uid'   => $newUserId,
                                ':eid'   => $employeeId,
                                ':dept'  => $dept,
                                ':desig' => $designation,
                                ':phone' => $phone
                            ]);
                            $db->commit();

                            redirect(BASE_URL . 'user/login.php', 'Faculty registration successful! Please log in to start sharing notes.', 'success');
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Registration Error: " . $e->getMessage());
            $error = 'An unexpected database error occurred during registration. Please try again.';
        }
    }
}

$pageTitle = "Create Academic Account";
$customCss = ['auth.css'];
$customJs = ['auth.js'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-card wide">
        <div class="auth-header">
            <h4 class="fw-bold mb-1">Create Your College Portal Account</h4>
            <p class="text-white-50 small mb-0">Join students and faculty on our centralized notes platform</p>
        </div>

        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div><?= e($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Role Selector Tabs -->
            <ul class="nav nav-tabs auth-nav-tabs mb-4 justify-content-center" id="regTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'student' ? 'active' : '' ?>" id="student-tab" data-bs-toggle="tab" data-bs-target="#studentPane" type="button" role="tab" onclick="document.getElementById('regTypeStudent').checked = true;">
                        <i class="fas fa-user-graduate me-2"></i> Student Registration
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'teacher' ? 'active' : '' ?>" id="teacher-tab" data-bs-toggle="tab" data-bs-target="#teacherPane" type="button" role="tab" onclick="document.getElementById('regTypeTeacher').checked = true;">
                        <i class="fas fa-chalkboard-teacher me-2"></i> Faculty / Teacher Registration
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="regTabContent">
                <!-- ================= STUDENT FORM ================= -->
                <div class="tab-pane fade <?= $activeTab === 'student' ? 'show active' : '' ?>" id="studentPane" role="tabpanel">
                    <form action="<?= BASE_URL ?>user/register.php" method="POST" class="validate-auth-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="reg_type" value="student" id="regTypeStudent">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Alex Rivera" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Student ID / Roll No. *</label>
                                <input type="text" name="student_id" class="form-control" placeholder="e.g. STU-2024-CS01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Academic Email *</label>
                                <input type="email" name="email" class="form-control" placeholder="student@collegenotes.edu" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="+1 555-0123">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Dept...</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Electronics & Communication">Electronics & Communication</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Current Semester *</label>
                                <select name="semester" class="form-select" required>
                                    <option value="">Select Sem...</option>
                                    <?php foreach ($semesters as $sem): ?>
                                        <option value="<?= e($sem['semester_name']) ?>"><?= e($sem['semester_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Batch Year *</label>
                                <input type="text" name="batch" class="form-control" placeholder="e.g. 2023-2027" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Create Password *</label>
                                <input type="password" name="password" id="stuPass" class="form-control" placeholder="Minimum 6 characters" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Confirm Password *</label>
                                <input type="password" name="confirm_password" id="stuPassConfirm" class="form-control" placeholder="Re-type password" required>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                                <i class="fas fa-user-plus me-2"></i> Register as Student
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ================= TEACHER FORM ================= -->
                <div class="tab-pane fade <?= $activeTab === 'teacher' ? 'show active' : '' ?>" id="teacherPane" role="tabpanel">
                    <form action="<?= BASE_URL ?>user/register.php" method="POST" class="validate-auth-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="reg_type" value="teacher" id="regTypeTeacher">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name (with Title) *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Robert Vance" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Faculty / Employee ID *</label>
                                <input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP-CS-302" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Faculty Email *</label>
                                <input type="email" name="email" class="form-control" placeholder="faculty@collegenotes.edu" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="+1 555-0199">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Dept...</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Electronics & Communication">Electronics & Communication</option>
                                    <option value="Information Technology">Information Technology</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Designation *</label>
                                <select name="designation" class="form-select" required>
                                    <option value="Professor">Professor</option>
                                    <option value="Associate Professor">Associate Professor</option>
                                    <option value="Assistant Professor">Assistant Professor</option>
                                    <option value="Lecturer">Lecturer</option>
                                    <option value="Head of Department (HOD)">Head of Department (HOD)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Create Password *</label>
                                <input type="password" name="password" id="tchPass" class="form-control" placeholder="Minimum 6 characters" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Confirm Password *</label>
                                <input type="password" name="confirm_password" id="tchPassConfirm" class="form-control" placeholder="Re-type password" required>
                            </div>
                        </div>

                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                                <i class="fas fa-chalkboard-teacher me-2"></i> Register as Faculty
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top text-center">
                <span class="small text-muted">Already have a registered portal account?</span>
                <a href="<?= BASE_URL ?>user/login.php" class="small fw-bold text-decoration-none ms-1">Sign in here</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
