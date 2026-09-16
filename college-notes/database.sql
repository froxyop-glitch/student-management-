-- ==========================================================
-- College Notes Management System - Database Schema & Seed
-- Target Engine: MySQL 8+ / MariaDB 10.4+
-- Database: college_notes
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `college_notes` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `college_notes`;

-- Disable foreign key checks during creation/reset
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'teacher', 'student') NOT NULL,
    `status` ENUM('active', 'inactive', 'blocked') NOT NULL DEFAULT 'active',
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: teachers
-- --------------------------------------------------------
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `employee_id` VARCHAR(50) NOT NULL UNIQUE,
    `department` VARCHAR(100) NOT NULL,
    `designation` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(25) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_teacher_dept` (`department`),
    CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: students
-- --------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL UNIQUE,
    `student_id` VARCHAR(50) NOT NULL UNIQUE,
    `semester` VARCHAR(20) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `batch` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(25) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_student_dept` (`department`),
    INDEX `idx_student_sem` (`semester`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: semesters
-- --------------------------------------------------------
DROP TABLE IF EXISTS `semesters`;
CREATE TABLE `semesters` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `semester_number` INT NOT NULL UNIQUE,
    `semester_name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: subjects
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `semester` VARCHAR(20) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subjects_sem_dept` (`semester`, `department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notes
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notes`;
CREATE TABLE `notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(20) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_notes_teacher` (`teacher_id`),
    INDEX `idx_notes_subject` (`subject_id`),
    INDEX `idx_notes_dept_sem` (`department`, `semester`),
    INDEX `idx_notes_status` (`status`),
    CONSTRAINT `fk_notes_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notes_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: downloads
-- --------------------------------------------------------
DROP TABLE IF EXISTS `downloads`;
CREATE TABLE `downloads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `note_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_downloads_note` (`note_id`),
    INDEX `idx_downloads_user` (`user_id`),
    CONSTRAINT `fk_downloads_note` FOREIGN KEY (`note_id`) REFERENCES `notes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_downloads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: login_logs (Brute force and security audit)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE `login_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `status` ENUM('success', 'failed') NOT NULL,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_logs_email_ip` (`email`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- SEED DATA
-- Default Passwords:
-- Admin:   Admin@123
-- Teacher: Teacher@123
-- Student: Student@123
-- ==========================================================

-- 1. Insert Semesters (1 to 8)
INSERT INTO `semesters` (`semester_number`, `semester_name`) VALUES
(1, 'Semester 1'),
(2, 'Semester 2'),
(3, 'Semester 3'),
(4, 'Semester 4'),
(5, 'Semester 5'),
(6, 'Semester 6'),
(7, 'Semester 7'),
(8, 'Semester 8');

-- 2. Insert Users
-- Passwords generated via password_hash('...', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`) VALUES
-- 1 Admin
(1, 'System Administrator', 'admin@collegenotes.edu', '$2y$10$Um6V/megBdTUOoZC13Wk8e/48chNKc2qx0PaexAxMhleith8zZgpK', 'admin', 'active', NOW()),
-- 2 Teachers
(2, 'Dr. John Smith', 'teacher.john@collegenotes.edu', '$2y$10$LQp9eqZq2p53OPGmizlUfOY1.1vjI9nwp754lEQcgK1FCNYw44AV.', 'teacher', 'active', NOW()),
(3, 'Prof. Sarah Jenkins', 'teacher.sarah@collegenotes.edu', '$2y$10$LQp9eqZq2p53OPGmizlUfOY1.1vjI9nwp754lEQcgK1FCNYw44AV.', 'teacher', 'active', NOW()),
-- 5 Students
(4, 'Alex Rivera', 'student.alex@collegenotes.edu', '$2y$10$tVLD7XbxJLF8q.vVSAq1RuC3SO2WulzdXqb7rzCbGniuprUa9l/Wi', 'student', 'active', NOW()),
(5, 'Emma Watson', 'student.emma@collegenotes.edu', '$2y$10$tVLD7XbxJLF8q.vVSAq1RuC3SO2WulzdXqb7rzCbGniuprUa9l/Wi', 'student', 'active', NOW()),
(6, 'Michael Chang', 'student.michael@collegenotes.edu', '$2y$10$tVLD7XbxJLF8q.vVSAq1RuC3SO2WulzdXqb7rzCbGniuprUa9l/Wi', 'student', 'active', NOW()),
(7, 'Sophia Patel', 'student.sophia@collegenotes.edu', '$2y$10$tVLD7XbxJLF8q.vVSAq1RuC3SO2WulzdXqb7rzCbGniuprUa9l/Wi', 'student', 'active', NOW()),
(8, 'David Kim', 'student.david@collegenotes.edu', '$2y$10$tVLD7XbxJLF8q.vVSAq1RuC3SO2WulzdXqb7rzCbGniuprUa9l/Wi', 'student', 'active', NOW());

-- 3. Insert Teachers Details
INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `department`, `designation`, `phone`) VALUES
(1, 2, 'EMP-CS-101', 'Computer Science', 'Professor & HOD', '+1 555-0101'),
(2, 3, 'EMP-EC-204', 'Electronics & Communication', 'Associate Professor', '+1 555-0204');

-- 4. Insert Students Details
INSERT INTO `students` (`id`, `user_id`, `student_id`, `semester`, `department`, `batch`, `phone`) VALUES
(1, 4, 'STU-2023-CS01', 'Semester 5', 'Computer Science', '2022-2026', '+1 555-1101'),
(2, 5, 'STU-2023-CS02', 'Semester 5', 'Computer Science', '2022-2026', '+1 555-1102'),
(3, 6, 'STU-2024-EC09', 'Semester 3', 'Electronics & Communication', '2023-2027', '+1 555-1103'),
(4, 7, 'STU-2022-CS14', 'Semester 6', 'Computer Science', '2021-2025', '+1 555-1104'),
(5, 8, 'STU-2023-IT05', 'Semester 4', 'Information Technology', '2022-2026', '+1 555-1105');

-- 5. Insert Subjects
INSERT INTO `subjects` (`id`, `name`, `code`, `semester`, `department`) VALUES
(1, 'Database Management Systems', 'CS501', 'Semester 5', 'Computer Science'),
(2, 'Operating Systems & Architecture', 'CS502', 'Semester 5', 'Computer Science'),
(3, 'Computer Networks & Security', 'CS503', 'Semester 5', 'Computer Science'),
(4, 'Data Structures & Algorithms', 'CS301', 'Semester 3', 'Computer Science'),
(5, 'Digital Signal Processing', 'EC302', 'Semester 3', 'Electronics & Communication'),
(6, 'Microprocessors & Microcontrollers', 'EC501', 'Semester 5', 'Electronics & Communication'),
(7, 'Web Development & Cloud Computing', 'IT401', 'Semester 4', 'Information Technology'),
(8, 'Software Engineering & Agile', 'CS601', 'Semester 6', 'Computer Science');

-- 6. Insert Notes
INSERT INTO `notes` (`id`, `teacher_id`, `subject_id`, `title`, `description`, `file_name`, `file_path`, `file_type`, `file_size`, `semester`, `department`, `status`, `created_at`) VALUES
(1, 1, 1, 'Module 1: Relational Algebra & SQL Normalization', 'Complete lecture slides and exercises covering 1NF, 2NF, 3NF, BCNF with real-world schema examples.', 'sample_dbms_normalization.pdf', 'assets/uploads/notes/sample_dbms_normalization.pdf', 'pdf', 1048576, 'Semester 5', 'Computer Science', 'published', NOW() - INTERVAL 10 DAY),
(2, 1, 1, 'Module 2: Transaction Management & Concurrency Control', 'Detailed notes on ACID properties, 2-Phase Locking, Timestamp ordering, and Deadlock resolution algorithms.', 'sample_transactions_concurrency.pdf', 'assets/uploads/notes/sample_transactions_concurrency.pdf', 'pdf', 2097152, 'Semester 5', 'Computer Science', 'published', NOW() - INTERVAL 8 DAY),
(3, 1, 2, 'Chapter 3: CPU Scheduling Algorithms', 'Comparison of FCFS, SJF, Priority, and Round Robin scheduling with solved numerical problems.', 'sample_cpu_scheduling.docx', 'assets/uploads/notes/sample_cpu_scheduling.docx', 'docx', 524288, 'Semester 5', 'Computer Science', 'published', NOW() - INTERVAL 5 DAY),
(4, 2, 5, 'Unit 1: Fast Fourier Transform (FFT) Decimation', 'Mathematical derivations and frequency domain sampling graphs for DIT-FFT and DIF-FFT architectures.', 'sample_dsp_fft_notes.pdf', 'assets/uploads/notes/sample_dsp_fft_notes.pdf', 'pdf', 1572864, 'Semester 3', 'Electronics & Communication', 'published', NOW() - INTERVAL 3 DAY),
(5, 2, 6, '8086 Assembly Language Programming Guide', 'Instruction set reference, addressing modes, and sample lab programs for 8086 microprocessor interfacing.', 'sample_8086_assembly.pdf', 'assets/uploads/notes/sample_8086_assembly.pdf', 'pdf', 3145728, 'Semester 5', 'Electronics & Communication', 'published', NOW() - INTERVAL 2 DAY),
(6, 1, 4, 'Graph Algorithms: Dijkstra, Bellman-Ford & Floyd-Warshall', 'Step-by-step trace diagrams, time complexity proofs, and pseudo-code implementations.', 'sample_graph_algorithms.pptx', 'assets/uploads/notes/sample_graph_algorithms.pptx', 'pptx', 4194304, 'Semester 3', 'Computer Science', 'published', NOW() - INTERVAL 1 DAY);

-- 7. Insert Sample Downloads
INSERT INTO `downloads` (`note_id`, `user_id`, `ip_address`, `user_agent`, `downloaded_at`) VALUES
(1, 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 7 DAY),
(1, 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 6 DAY),
(2, 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 5 DAY),
(4, 6, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 2 DAY),
(5, 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', NOW() - INTERVAL 1 DAY);
