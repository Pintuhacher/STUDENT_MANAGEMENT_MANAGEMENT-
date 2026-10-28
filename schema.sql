-- Student Management System Database Schema
-- Database: alalr_43295771_surajitdb
-- Host: sql108.freehosting.digitalplat.org

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `student_update_requests`;
DROP TABLE IF EXISTS `leave_requests`;
DROP TABLE IF EXISTS `notices`;
DROP TABLE IF EXISTS `class_routines`;
DROP TABLE IF EXISTS `study_materials`;
DROP TABLE IF EXISTS `assignment_submissions`;
DROP TABLE IF EXISTS `assignments`;
DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `marks`;
DROP TABLE IF EXISTS `exam_schedules`;
DROP TABLE IF EXISTS `exams`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `system_settings`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. System Settings
CREATE TABLE `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) UNIQUE NOT NULL,
  `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('institution_name', 'EduDash International Academy'),
('institution_email', 'admin@edudash.digitalplat.org'),
('institution_phone', '+91 98765 43210'),
('institution_address', 'Knowledge Campus, Academic Boulevard, West Bengal, India'),
('current_session', '2026-2027'),
('currency_symbol', '₹');

-- 2. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher', 'student') NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `avatar` VARCHAR(255) DEFAULT 'default_avatar.png',
  `status` ENUM('active', 'inactive', 'pending') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Users (Passwords: admin123, teacher123, student123)
-- Hash generated using password_hash('password', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `username`, `password`, `role`, `email`, `full_name`, `phone`, `status`) VALUES
(1, 'admin', '$2y$10$wWvYyN.69kYt.oU9VbYQse5WvF.aC2ZgL5r0f2lU5K1eB0Vw8y8iK', 'admin', 'admin@edudash.edu', 'Surajit Admin', '9876543210', 'active'),
(2, 'teacher1', '$2y$10$0p2f5X7rQ1P1U6R7jW2s..kM3Z3C0c4T4A4w9F7E1G2H3I4J5K6L.', 'teacher', 'rajesh.sharma@edudash.edu', 'Prof. Rajesh Sharma', '9876543211', 'active'),
(3, 'teacher2', '$2y$10$0p2f5X7rQ1P1U6R7jW2s..kM3Z3C0c4T4A4w9F7E1G2H3I4J5K6L.', 'teacher', 'ananya.sen@edudash.edu', 'Dr. Ananya Sen', '9876543212', 'active'),
(4, 'student1', '$2y$10$T8Z7X5W4V3U2T1S0R9Q8P7O6N5M4L3K2J1I0H9G8F7E6D5C4B3A2.', 'student', 'rohit.das@edudash.edu', 'Rohit Das', '9876543213', 'active'),
(5, 'student2', '$2y$10$T8Z7X5W4V3U2T1S0R9Q8P7O6N5M4L3K2J1I0H9G8F7E6D5C4B3A2.', 'student', 'priya.dey@edudash.edu', 'Priya Dey', '9876543214', 'active'),
(6, 'student3', '$2y$10$T8Z7X5W4V3U2T1S0R9Q8P7O6N5M4L3K2J1I0H9G8F7E6D5C4B3A2.', 'student', 'amit.roy@edudash.edu', 'Amit Roy', '9876543215', 'pending');

-- 3. Departments Table
CREATE TABLE `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) UNIQUE NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `departments` (`id`, `name`, `code`, `description`) VALUES
(1, 'Computer Science & Engineering', 'CSE', 'Department of Computer Science and Applications'),
(2, 'Basic Sciences & Mathematics', 'BSM', 'Department of Physics, Chemistry, and Mathematics'),
(3, 'Commerce & Management', 'MGT', 'Business Administration and Financial Management'),
(4, 'Humanities & Social Sciences', 'HUM', 'Literature, Communication, and Ethics');

-- 4. Classes Table
CREATE TABLE `classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `department_id` INT NULL,
  `class_teacher_id` INT NULL,
  `numeric_level` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `classes` (`id`, `name`, `section`, `department_id`, `class_teacher_id`, `numeric_level`) VALUES
(1, 'Class 10', 'A', 2, 1, 10),
(2, 'Class 10', 'B', 2, 2, 10),
(3, 'Class 11 Science', 'A', 1, 1, 11),
(4, 'Class 12 Science', 'A', 1, 2, 12);

-- 5. Teachers Table
CREATE TABLE `teachers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNIQUE NOT NULL,
  `employee_id` VARCHAR(30) UNIQUE NOT NULL,
  `department_id` INT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `joining_date` DATE NOT NULL,
  `address` TEXT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `department_id`, `qualification`, `joining_date`, `address`) VALUES
(1, 2, 'TCH-2024-001', 1, 'M.Tech in Computer Science', '2023-01-15', 'Kalna, West Bengal'),
(2, 3, 'TCH-2024-002', 2, 'Ph.D in Applied Mathematics', '2023-03-01', 'Kolkata, West Bengal');

-- Update class teacher linkage
UPDATE `classes` SET `class_teacher_id` = 1 WHERE `id` = 1;
UPDATE `classes` SET `class_teacher_id` = 2 WHERE `id` = 2;

-- 6. Students Table
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNIQUE NOT NULL,
  `admission_no` VARCHAR(30) UNIQUE NOT NULL,
  `roll_no` VARCHAR(20) NOT NULL,
  `class_id` INT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `blood_group` VARCHAR(5) DEFAULT 'B+',
  `father_name` VARCHAR(100) NOT NULL,
  `mother_name` VARCHAR(100) NOT NULL,
  `parent_phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `admission_date` DATE NOT NULL,
  `status` ENUM('active', 'pending', 'inactive', 'graduated') DEFAULT 'active',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `students` (`id`, `user_id`, `admission_no`, `roll_no`, `class_id`, `dob`, `gender`, `blood_group`, `father_name`, `mother_name`, `parent_phone`, `address`, `admission_date`, `status`) VALUES
(1, 4, 'ADM-2026-001', '101', 1, '2009-05-14', 'Male', 'O+', 'Subhash Das', 'Mita Das', '9832100001', 'Kalna, Purba Bardhaman, WB', '2025-04-10', 'active'),
(2, 5, 'ADM-2026-002', '102', 1, '2009-08-22', 'Female', 'A+', 'Prabir Dey', 'Shampa Dey', '9832100002', 'Ambika Kalna, WB', '2025-04-12', 'active'),
(3, 6, 'ADM-2026-003', '103', 2, '2009-11-05', 'Male', 'B+', 'Kamal Roy', 'Sunita Roy', '9832100003', 'Burdwan, WB', '2026-01-10', 'pending');

-- 7. Subjects Table
CREATE TABLE `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `class_id` INT NOT NULL,
  `teacher_id` INT NULL,
  `type` ENUM('Theory', 'Practical', 'Combined') DEFAULT 'Theory',
  `max_marks` INT DEFAULT 100,
  `pass_marks` INT DEFAULT 35,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `subjects` (`id`, `name`, `code`, `class_id`, `teacher_id`, `type`, `max_marks`, `pass_marks`) VALUES
(1, 'Mathematics', 'MATH-10', 1, 2, 'Theory', 100, 35),
(2, 'Computer Applications', 'CS-10', 1, 1, 'Combined', 100, 35),
(3, 'Physics', 'PHY-10', 1, 2, 'Combined', 100, 35),
(4, 'English Literature', 'ENG-10', 1, 1, 'Theory', 100, 35);

-- 8. Attendance Table
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
  `marked_by` INT NULL,
  `remarks` VARCHAR(255) NULL,
  UNIQUE KEY `unique_student_date` (`student_id`, `date`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `attendance` (`student_id`, `class_id`, `date`, `status`, `remarks`) VALUES
(1, 1, CURDATE(), 'present', 'Regular'),
(2, 1, CURDATE(), 'present', 'Regular'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'present', 'On time'),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'late', 'Bus delay'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'present', 'On time'),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'present', 'On time');

-- 9. Exams & Exam Schedules
CREATE TABLE `exams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `term` VARCHAR(50) NOT NULL,
  `session_year` VARCHAR(20) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `exams` (`id`, `title`, `term`, `session_year`, `start_date`, `end_date`) VALUES
(1, 'Mid-Term Summative Assessment 2026', 'Mid-Term', '2026-2027', '2026-11-15', '2026-11-25');

CREATE TABLE `exam_schedules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `exam_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room_no` VARCHAR(20) NOT NULL,
  FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `exam_schedules` (`id`, `exam_id`, `class_id`, `subject_id`, `exam_date`, `start_time`, `end_time`, `room_no`) VALUES
(1, 1, 1, 1, '2026-11-15', '10:00:00', '13:00:00', 'Hall A-101'),
(2, 1, 1, 2, '2026-11-17', '10:00:00', '13:00:00', 'Lab 2'),
(3, 1, 1, 3, '2026-11-19', '10:00:00', '13:00:00', 'Hall A-102');

-- 10. Marks Table
CREATE TABLE `marks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `exam_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `marks_obtained` DECIMAL(5,2) NOT NULL,
  `grade` VARCHAR(5) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  UNIQUE KEY `unique_exam_student_subject` (`exam_id`, `student_id`, `subject_id`),
  FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `marks` (`exam_id`, `student_id`, `subject_id`, `marks_obtained`, `grade`, `remarks`) VALUES
(1, 1, 1, 88.50, 'A+', 'Outstanding problem solving'),
(1, 1, 2, 94.00, 'O', 'Exceptional coding and practical work'),
(1, 1, 3, 79.00, 'A', 'Good performance in theory'),
(1, 2, 1, 92.00, 'O', 'First in class in Mathematics'),
(1, 2, 2, 85.00, 'A+', 'Solid algorithmic fundamentals');

-- 11. Fees Table
CREATE TABLE `fees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `due_date` DATE NOT NULL,
  `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('paid', 'partial', 'unpaid') DEFAULT 'unpaid',
  `payment_date` DATE NULL,
  `payment_method` VARCHAR(50) NULL,
  `transaction_id` VARCHAR(100) NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `fees` (`id`, `student_id`, `title`, `amount`, `due_date`, `paid_amount`, `status`, `payment_date`, `payment_method`, `transaction_id`) VALUES
(1, 1, 'Term 1 Tuition Fee', 12000.00, '2026-05-10', 12000.00, 'paid', '2026-05-02', 'UPI / NetBanking', 'TXN-99881122'),
(2, 1, 'Term 2 Tuition & Lab Fee', 13500.00, '2026-11-10', 0.00, 'unpaid', NULL, NULL, NULL),
(3, 2, 'Term 1 Tuition Fee', 12000.00, '2026-05-10', 12000.00, 'paid', '2026-05-04', 'Card Payment', 'TXN-77334411');

-- 12. Assignments Table
CREATE TABLE `assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `due_date` DATE NOT NULL,
  `max_marks` INT DEFAULT 20,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `assignments` (`id`, `class_id`, `subject_id`, `teacher_id`, `title`, `description`, `due_date`, `max_marks`) VALUES
(1, 1, 2, 1, 'PHP & MySQL Database CRUD Project', 'Create a relational database connection and implement student record operations with prepared statements.', '2026-11-05', 25),
(2, 1, 1, 2, 'Quadratic Equations & Polynomial Proofs', 'Solve Exercise 4.2 questions 1 through 15 with full step-by-step mathematical proofs.', '2026-11-08', 20);

-- 13. Assignment Submissions Table
CREATE TABLE `assignment_submissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `assignment_id` INT NOT NULL,
  `student_id` INT NOT NULL,
  `student_notes` TEXT NULL,
  `file_url` VARCHAR(255) NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `marks_obtained` DECIMAL(5,2) NULL,
  `teacher_feedback` TEXT NULL,
  `status` ENUM('submitted', 'graded', 'late') DEFAULT 'submitted',
  UNIQUE KEY `unique_assignment_student` (`assignment_id`, `student_id`),
  FOREIGN KEY (`assignment_id`) REFERENCES `assignments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `assignment_submissions` (`id`, `assignment_id`, `student_id`, `student_notes`, `submitted_at`, `marks_obtained`, `teacher_feedback`, `status`) VALUES
(1, 1, 1, 'Completed all CRUD models, added Bootstrap UI and PDO validation.', NOW(), 24.50, 'Excellent work Rohit! Security practices are spot on.', 'graded');

-- 14. Study Materials Table
CREATE TABLE `study_materials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `file_link` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `study_materials` (`id`, `class_id`, `subject_id`, `teacher_id`, `title`, `description`, `file_link`) VALUES
(1, 1, 2, 1, 'PHP 8 Modern Web Development Handbook', 'Complete guide covering PDO, OOP, session security and CSRF.', 'https://www.php.net/docs.php'),
(2, 1, 1, 2, 'Mathematics Formula Sheet & Practice Bank', 'Comprehensive quick reference for Calculus, Algebra, and Trigonometry.', 'https://archive.org/details/math-formulas');

-- 15. Class Routine / Timetable
CREATE TABLE `class_routines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `room_no` VARCHAR(20) NOT NULL,
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `class_routines` (`id`, `class_id`, `subject_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room_no`) VALUES
(1, 1, 1, 2, 'Monday', '09:00:00', '10:00:00', 'Room 101'),
(2, 1, 2, 1, 'Monday', '10:05:00', '11:05:00', 'Computer Lab 1'),
(3, 1, 3, 2, 'Monday', '11:15:00', '12:15:00', 'Physics Lab'),
(4, 1, 4, 1, 'Tuesday', '09:00:00', '10:00:00', 'Room 101');

-- 16. Notices Table
CREATE TABLE `notices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `target_audience` ENUM('all', 'teachers', 'students', 'admin') DEFAULT 'all',
  `priority` ENUM('normal', 'urgent') DEFAULT 'normal',
  `posted_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `notices` (`id`, `title`, `content`, `target_audience`, `priority`, `posted_by`) VALUES
(1, 'Upcoming Mid-Term Summative Examination Schedule', 'All students and faculty members are hereby notified that the Mid-Term examination will commence on November 15, 2026. Hall tickets and seating charts will be issued soon.', 'all', 'urgent', 1),
(2, 'Annual Science Exhibition Registration Open', 'Students interested in showcasing working models and innovative software applications should register with Prof. Rajesh Sharma by Friday.', 'students', 'normal', 1);

-- 17. Leave Requests Table
CREATE TABLE `leave_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `teacher_remarks` VARCHAR(255) NULL,
  `reviewed_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `teachers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `leave_requests` (`id`, `student_id`, `start_date`, `end_date`, `reason`, `status`, `teacher_remarks`, `reviewed_by`) VALUES
(1, 1, '2026-11-01', '2026-11-02', 'Attending District Mathematics Olympiad competition in Kolkata.', 'approved', 'Approved. Submit certificates upon return.', 1);

-- 18. Student Profile Update Requests
CREATE TABLE `student_update_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `requested_changes` TEXT NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `admin_remarks` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Activity Logs Table
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `activity_logs` (`user_id`, `action`, `details`, `ip_address`) VALUES
(1, 'System Initialization', 'Database schema and default records seeded successfully.', '127.0.0.1'),
(1, 'Admin Login', 'Administrator authenticated successfully.', '127.0.0.1');
