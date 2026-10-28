<?php
$role = $_SESSION['user_role'] ?? 'guest';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar d-flex flex-column flex-shrink-0">
    <div class="brand">
        <i class="fa-solid fa-graduation-cap text-warning fs-4"></i>
        <span>EduDash SMS</span>
    </div>

    <div class="px-3 py-2 border-bottom border-secondary border-opacity-25 d-flex align-items-center gap-2">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; font-weight:600;">
            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div class="small overflow-hidden text-truncate">
            <div class="text-white fw-semibold text-truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
            <span class="badge badge-role-<?php echo $role; ?> text-uppercase" style="font-size:0.65rem;">
                <?php echo htmlspecialchars($role); ?>
            </span>
        </div>
    </div>

    <ul class="nav nav-pills flex-column mb-auto py-3">
        <?php if ($role === 'admin'): ?>
            <li class="nav-header">Main Control</li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/students.php" class="nav-link <?php echo in_array($current_page, ['students.php', 'student_approve.php']) ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate"></i> Students
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/student_approve.php" class="nav-link <?php echo $current_page == 'student_approve.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-check"></i> Registration Approval
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/teachers.php" class="nav-link <?php echo $current_page == 'teachers.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user"></i> Teachers
                </a>
            </li>

            <li class="nav-header">Academic Management</li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/classes.php" class="nav-link <?php echo $current_page == 'classes.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-school"></i> Classes & Sections
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/promotion.php" class="nav-link <?php echo $current_page == 'promotion.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-arrow-trend-up"></i> Class Promotion
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/departments.php" class="nav-link <?php echo $current_page == 'departments.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-sitemap"></i> Departments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/subjects.php" class="nav-link <?php echo $current_page == 'subjects.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-book-open"></i> Subjects
                </a>
            </li>

            <li class="nav-header">Operations & Finance</li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-check"></i> Attendance
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/marks.php" class="nav-link <?php echo $current_page == 'marks.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-simple"></i> Marks & Results
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-receipt"></i> Fees Management
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/notices.php" class="nav-link <?php echo $current_page == 'notices.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bullhorn"></i> Notice Board
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice"></i> Reports
                </a>
            </li>

            <li class="nav-header">Administration</li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gear"></i> System Settings
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/admin/logs.php" class="nav-link <?php echo $current_page == 'logs.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Activity Logs
                </a>
            </li>

        <?php elseif ($role === 'teacher'): ?>
            <li class="nav-header">Teacher Control</li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/my_classes.php" class="nav-link <?php echo $current_page == 'my_classes.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users-rectangle"></i> My Classes & Students
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-clipboard-user"></i> Take Attendance
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/assignments.php" class="nav-link <?php echo $current_page == 'assignments.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-signature"></i> Assignments & Marks
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/exam_schedule.php" class="nav-link <?php echo $current_page == 'exam_schedule.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-days"></i> Exam Schedule
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/performance.php" class="nav-link <?php echo $current_page == 'performance.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-square-poll-vertical"></i> Performance Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/study_materials.php" class="nav-link <?php echo $current_page == 'study_materials.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-folder-open"></i> Upload Study Materials
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/teacher/leave_requests.php" class="nav-link <?php echo $current_page == 'leave_requests.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-envelope-open-text"></i> Leave Requests
                </a>
            </li>

        <?php elseif ($role === 'student'): ?>
            <li class="nav-header">Student Portal</li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/profile.php" class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-id-card"></i> My Profile & Updates
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/attendance.php" class="nav-link <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-check"></i> Attendance Record
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/results.php" class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-award"></i> Exam Results & Marks
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/assignments.php" class="nav-link <?php echo $current_page == 'assignments.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-arrow-up"></i> Submit Assignments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/routine.php" class="nav-link <?php echo $current_page == 'routine.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-table-cells"></i> Class & Exam Routine
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/study_materials.php" class="nav-link <?php echo $current_page == 'study_materials.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-download"></i> Study Materials
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/fees.php" class="nav-link <?php echo $current_page == 'fees.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-wallet"></i> Fees & Payments
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo BASE_URL; ?>/student/leave_request.php" class="nav-link <?php echo $current_page == 'leave_request.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-plane-departure"></i> Apply for Leave
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
        <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline-danger w-100 btn-sm d-flex align-items-center justify-content-center gap-2">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out
        </a>
    </div>
</aside>
