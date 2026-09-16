# College Notes Management System

A secure, production-grade academic repository portal engineered in **Core PHP 8+**, **MySQL 8+**, and **Bootstrap 5.3**. Designed for universities and colleges to facilitate organized study resource sharing between faculty instructors and students with enterprise-grade authorization and security controls.

---

## Table of Contents
1. [Project Overview](#project-overview)
2. [Key Capabilities & Features](#key-capabilities--features)
3. [System Architecture & DFD Interpretation](#system-architecture--dfd-interpretation)
4. [Technology Stack](#technology-stack)
5. [Folder & Directory Structure](#folder--directory-structure)
6. [XAMPP Setup & Installation Guide](#xampp-setup--installation-guide)
7. [Database Setup & Schema](#database-setup--schema)
8. [Default Seed Credentials](#default-seed-credentials)
9. [Role Permissions & Workflows](#role-permissions--workflows)
10. [Cybersecurity Architecture](#cybersecurity-architecture)
11. [Troubleshooting & FAQ](#troubleshooting--faq)

---

## 1. Project Overview
In higher education environments, lecture notes, syllabus modules, and revision guides are frequently scattered across email threads and messaging channels. **College Notes Management System** addresses this problem by delivering a single, unified, institutional repository:
- **Students** can discover, filter, read (via embedded PDF viewer), and download verified academic materials.
- **Teachers/Faculty** have dedicated dashboards to publish, edit, manage, and track student downloads on their course materials.
- **Administrators** control access, manage student rosters, manage faculty departments, moderate notes, audit login logs, and export SQL backups with zero credential exposure.

---

## 2. Key Capabilities & Features
- **Clean Core PHP 8+ Architecture**: Zero heavy framework overhead; highly performant, modular, and maintainable.
- **Interactive PDF Viewer**: Embedded in-browser viewing for `.pdf` documents without needing third-party downloads.
- **Multi-Role Authorization**: Strict separation between Admin, Faculty, and Student roles with direct URL bypass prevention.
- **Multi-Factor File Validation**: Double-layer validation (client-side size/extension check + server-side MIME type detection via PHP `finfo` and randomized collision-resistant file hashing).
- **Automated Download Auditing**: Comprehensive logging of IP addresses, timestamps, and browser user agents per note download.
- **Brute Force & Rate Limiting**: Exponential throttling on login endpoints after 5 failed attempts within 15 minutes.
- **Automated Database Backup**: Single-click downloadable SQL dump utility for IT administrators.

---

## 3. System Architecture & DFD Interpretation

### DFD Level 0 (Context Diagram)
```
  [ ADMIN ]                        [ STUDENTS & TEACHERS ]
      |                                      |
      | (Manage Users, Notes, Backup)        | (Register, Upload/Read Notes)
      v                                      v
  ======================================================
  |            SECURE COLLEGE NOTES PORTAL             |
  ======================================================
```

### DFD Level 1 (Functional Flow)
```
Landing Page (index.php)
       |
       +---> User Login / Register (user/login.php, user/register.php)
       |        |
       |        +---> Student Dashboard (user/dashboard.php)
       |        |        +---> Browse Notes (user/notes.php)
       |        |        +---> Note Details & PDF Viewer (user/note-view.php)
       |        |        +---> Secure Download & Audit (user/note-download.php)
       |        |        +---> Curriculum Subjects (user/subjects.php)
       |        |
       |        +---> Teacher Dashboard (teacher/dashboard.php)
       |                 +---> Upload New Note (teacher/create-note.php)
       |                 +---> My Notes CRUD (teacher/notes.php, edit-note.php, delete-note.php)
       |                 +---> Student Download Analytics
       |
       +---> Admin Login (admin/login.php)
                |
                +---> Admin Dashboard (admin/dashboard.php)
                         +---> Faculty CRUD (admin/teachers/)
                         +---> Student CRUD (admin/students/)
                         +---> Notes Moderation (admin/notes/)
                         +---> Subjects Management (admin/subjects/)
                         +---> SQL Database Backup (admin/backup.php)
```

---

## 4. Technology Stack
- **Backend**: Core PHP 8.0, 8.1, 8.2, 8.3+
- **Database**: MySQL 8.0+ / MariaDB 10.4+ (InnoDB engine, utf8mb4 encoding)
- **Database Driver**: PHP Data Objects (PDO) with strict parameterized prepared statements
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3.3, Font Awesome 6.5.2
- **Web Server**: Apache 2.4+ (with `mod_rewrite` and `mod_headers`)

---

## 5. Folder & Directory Structure
```
college-notes/
│
├── assets/
│   ├── css/
│   │   ├── style.css           # Global theme & components
│   │   ├── auth.css            # Split authentication card styling
│   │   ├── dashboard.css       # Student & teacher portal layouts
│   │   └── admin.css           # High-density admin controls & badges
│   │
│   ├── js/
│   │   ├── main.js             # Navigation, alerts, mobile toggler
│   │   ├── auth.js             # Client password toggler & validations
│   │   └── dashboard.js        # File size/type checks, filter submits
│   │
│   ├── img/                    # Logos and graphics
│   ├── icons/                  # Icon resources
│   └── uploads/
│       ├── .htaccess           # Prevents PHP script execution in uploads
│       └── notes/              # Sanitized unique document files
│
├── includes/
│   ├── config.php              # Global configuration & constants
│   ├── db.php                  # PDO Singleton database connection
│   ├── auth.php                # Session timeout, guards (requireRole)
│   ├── functions.php           # XSS escaping, CSRF tokens, rate limiter
│   ├── header.php              # Shared HTML <head> & styling imports
│   ├── footer.php              # Shared HTML footer & script imports
│   ├── navbar.php              # Context-aware top navigation bar
│   └── sidebar.php             # Role-based adaptive sidebar menu
│
├── admin/
│   ├── dashboard.php           # Central administrative control panel
│   ├── login.php               # Separate admin authentication gateway
│   ├── backup.php              # Secure SQL database export utility
│   ├── teachers/
│   │   ├── index.php           # Faculty list, search, and status table
│   │   ├── create.php          # Add verified faculty member
│   │   ├── edit.php            # Edit faculty details & department
│   │   └── delete.php          # Cascade delete faculty member
│   ├── students/
│   │   ├── index.php           # Student roster list & search
│   │   ├── create.php          # Enroll new student account
│   │   ├── edit.php            # Edit student details & semester
│   │   └── delete.php          # Delete student account
│   ├── notes/
│   │   ├── index.php           # Note moderation list & status toggles
│   │   ├── edit.php            # Update note properties & approval
│   │   └── delete.php          # Inappropriate note deletion controller
│   └── subjects/
│       └── index.php           # Academic subjects CRUD
│
├── user/
│   ├── dashboard.php           # Student dashboard with stats & recent notes
│   ├── login.php               # User login form with rate limiting
│   ├── register.php            # Student & teacher tabbed registration
│   ├── logout.php              # Secure session destruction
│   ├── notes.php               # Search, filter, and paginated notes
│   ├── note-view.php           # Embedded PDF viewer & note metadata
│   ├── note-download.php       # Secure stream download & audit recorder
│   ├── profile.php             # Student profile & password update
│   ├── subjects.php            # Subjects directory & note counts
│   └── downloads.php           # Personal student download history
│
├── teacher/
│   ├── dashboard.php           # Teacher dashboard with student engagement
│   ├── notes.php               # Teacher notes management list
│   ├── create-note.php         # Upload new note form with file check
│   ├── edit-note.php           # Edit existing note (strict ownership)
│   ├── delete-note.php         # Delete note & unlink file (ownership check)
│   └── profile.php             # Faculty profile & password management
│
├── index.php                   # Public landing page with features & metrics
├── 404.php                     # Unified 404/403/500 security error handler
├── .htaccess                   # Root security headers & routing
├── database.sql                # Full SQL schema & seed records
└── README.md                   # Comprehensive documentation
```

---

## 6. XAMPP Setup & Installation Guide

### Step 1: Copy Project Files to XAMPP
Copy or move the `college-notes` directory into your XAMPP web root:
```
C:/xampp/htdocs/college-notes/
```

### Step 2: Start Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

---

## 7. Database Setup & Schema
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click the **Import** tab in the top navigation bar.
3. Click **Choose File** and select `C:/xampp/htdocs/college-notes/database.sql`.
4. Click **Import** (or **Go**).
5. The import script automatically creates the `college_notes` database and populates all 7 core tables with initial seed data.

---

## 8. Default Seed Credentials

All passwords have been securely hashed using `password_hash($password, PASSWORD_DEFAULT)`:

| Role | Name | Email | Default Password |
| :--- | :--- | :--- | :--- |
| **Admin** | System Administrator | `admin@collegenotes.edu` | `Admin@123` |
| **Teacher** | Dr. John Smith | `teacher.john@collegenotes.edu` | `Teacher@123` |
| **Teacher** | Prof. Sarah Jenkins | `teacher.sarah@collegenotes.edu` | `Teacher@123` |
| **Student** | Alex Rivera | `student.alex@collegenotes.edu` | `Student@123` |
| **Student** | Emma Watson | `student.emma@collegenotes.edu` | `Student@123` |
| **Student** | Michael Chang | `student.michael@collegenotes.edu` | `Student@123` |
| **Student** | Sophia Patel | `student.sophia@collegenotes.edu` | `Student@123` |
| **Student** | David Kim | `student.david@collegenotes.edu` | `Student@123` |

> [!TIP]
> After your initial evaluation, change passwords via the Profile settings page.

---

## 9. Role Permissions & Workflows

### How Students Register & Access Notes
1. Visit `http://localhost/college-notes/user/register.php`.
2. Fill in Name, Student ID, Academic Email, Department, Semester, Batch, and Password.
3. Upon registration, log in at `user/login.php`.
4. The **Student Dashboard** highlights recent uploads, recommended notes, and curriculum subjects.
5. In **Browse Notes** (`user/notes.php`), search by keyword or filter by department, semester, and faculty.
6. Click **View Note** to read the summary and preview the PDF inline.
7. Click **Download Note** to retrieve the original file.

### How Teachers Upload & Manage Notes
1. Faculty can either register at `user/register.php` (Faculty Tab) or be directly provisioned by the Administrator.
2. Log in at `user/login.php`.
3. In the **Teacher Dashboard**, click **+ Upload New Note** (`teacher/create-note.php`).
4. Select the target Subject, Semester, and Department.
5. Choose an allowed file (`.pdf`, `.doc`, `.docx`, `.ppt`, `.pptx`, `.txt`).
6. Select status: **Published** (immediate visibility) or **Draft** (private).
7. Teachers can only edit and delete notes they uploaded.

### Administrator Privileges
1. Access the dedicated administrative gateway at `http://localhost/college-notes/admin/login.php`.
2. Access the **Admin Dashboard** (`admin/dashboard.php`) for global metrics, moderation queues, and login audit logs.
3. Manage faculty rosters (`admin/teachers/`) and student records (`admin/students/`).
4. Review and moderate notes (`admin/notes/`), with the ability to unpublish or delete inappropriate uploads.
5. Export full database backups at `admin/backup.php`.

---

## 10. Cybersecurity Architecture

1. **Prepared Statements with PDO**: Every SQL query across the application uses parameterized statements with emulated prepares disabled (`PDO::ATTR_EMULATE_PREPARES => false`), eliminating SQL injection.
2. **Cryptographic Password Hashing**: Passwords are saved with bcrypt hashing. Plaintext passwords are never logged or stored.
3. **CSRF (Cross-Site Request Forgery) Tokens**: Every state-changing form (POST/DELETE) generates and verifies a cryptographically secure 256-bit token (`hash_equals`).
4. **XSS Defense**: All user-supplied output is encoded using `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
5. **Secure File Upload Pipeline**:
   - Double-check file extensions against `ALLOWED_EXTENSIONS`.
   - Inspect true MIME types with PHP `finfo` against `ALLOWED_MIME_TYPES`.
   - Generate collision-resistant unique randomized filenames (`bin2hex(random_bytes(16)) . '.' . $ext`).
   - Block direct PHP execution inside `assets/uploads/` via dedicated `.htaccess`.
6. **Session Fixation & Hijacking Protection**:
   - Session IDs are regenerated immediately upon authentication (`session_regenerate_id(true)`).
   - Cookies are configured with `httponly = true` and `samesite = 'Lax'`.
   - Configurable session inactivity timeout (auto-logout after 30 minutes).
7. **Rate Limiting / Brute Force Defense**: Login attempts are logged in `login_logs`. Accounts/IPs are temporarily blocked after 5 failed attempts within 15 minutes.
8. **Directory Traversal Protection**: File download and stream controllers verify real filesystem paths with `realpath()` and boundary guards against directory traversal attacks.

---

## 11. Troubleshooting & FAQ

- **Database Connection Error (500 Error Page)**:
  Open `includes/config.php` and verify `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS`. Ensure MySQL is running in XAMPP.
- **File Upload Errors**:
  If uploading large files fails, check `upload_max_filesize` and `post_max_size` in your `php.ini` (or the values set in `.htaccess`).
- **404 Not Found on Subfolder**:
  The application automatically detects whether it is running in `localhost/college-notes/` or at the root. If deploying to another directory name, check `BASE_URL` in `includes/config.php`.
