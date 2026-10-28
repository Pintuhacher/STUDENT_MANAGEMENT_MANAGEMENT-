<?php
$page_title = 'Teacher Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

// Find teacher ID
$stmt = $pdo->prepare("SELECT id, employee_id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacher_id = $teacher['id'] ?? 0;

// Metrics
$assigned_classes_count = 0;
$total_students_count = 0;
$pending_leaves_count = 0;
$active_assignments_count = 0;

if ($teacher_id > 0) {
    // Classes assigned as class teacher or subject teacher
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT class_id) FROM (
        SELECT id as class_id FROM classes WHERE class_teacher_id = ?
        UNION
        SELECT class_id FROM subjects WHERE teacher_id = ?
    ) as assigned");
    $stmt->execute([$teacher_id, $teacher_id]);
    $assigned_classes_count = $stmt->fetchColumn();

    $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT s.id) 
                            FROM students s 
                            JOIN classes c ON s.class_id = c.id 
                            WHERE s.status = 'active' AND (c.class_teacher_id = ? OR c.id IN (SELECT class_id FROM subjects WHERE teacher_id = ?))");
    $stmt2->execute([$teacher_id, $teacher_id]);
    $total_students_count = $stmt2->fetchColumn();

    $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'");
    $stmt3->execute();
    $pending_leaves_count = $stmt3->fetchColumn();

    $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE teacher_id = ?");
    $stmt4->execute([$teacher_id]);
    $active_assignments_count = $stmt4->fetchColumn();
}

// Today's Routine Schedule
$today_day = date('l'); // e.g. Monday
$routine_stmt = $pdo->prepare("SELECT r.*, c.name as class_name, c.section, sub.name as subject_name 
                               FROM class_routines r 
                               JOIN classes c ON r.class_id = c.id 
                               JOIN subjects sub ON r.subject_id = sub.id 
                               WHERE r.teacher_id = ? AND r.day_of_week = ? 
                               ORDER BY r.start_time ASC");
$routine_stmt->execute([$teacher_id, $today_day]);
$today_routines = $routine_stmt->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h4>
                <p class="text-muted small mb-0">Teacher Portal - Faculty ID: <code><?php echo htmlspecialchars($teacher['employee_id'] ?? 'TCH'); ?></code></p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>/teacher/attendance.php" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                    <i class="fa-solid fa-clipboard-user me-1"></i> Mark Today's Attendance
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Assigned Classes</span>
                            <h3 class="fw-bold mb-0 mt-1"><?php echo $assigned_classes_count; ?></h3>
                            <span class="badge bg-primary bg-opacity-10 text-primary mt-1 small">Active Sections</span>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-school"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Students Under Care</span>
                            <h3 class="fw-bold mb-0 mt-1"><?php echo $total_students_count; ?></h3>
                            <span class="badge bg-success bg-opacity-10 text-success mt-1 small">Rosters Loaded</span>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-user-graduate"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Assignments Created</span>
                            <h3 class="fw-bold mb-0 mt-1"><?php echo $active_assignments_count; ?></h3>
                            <span class="badge bg-info bg-opacity-10 text-info mt-1 small">Coursework Active</span>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fa-solid fa-file-signature"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Leave Requests</span>
                            <h3 class="fw-bold mb-0 mt-1"><?php echo $pending_leaves_count; ?></h3>
                            <a href="<?php echo BASE_URL; ?>/teacher/leave_requests.php" class="badge bg-warning bg-opacity-10 text-warning mt-1 small text-decoration-none">Review Requests</a>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-envelope-open-text"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Today's Schedule -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-clock text-primary me-2"></i> Today's Schedule (<?php echo $today_day; ?>)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($today_routines)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fa-solid fa-mug-saucer fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                No scheduled lecture periods for you today.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($today_routines as $rt): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($rt['subject_name']); ?></div>
                                            <small class="text-muted">Class: <?php echo htmlspecialchars($rt['class_name'] . ' ' . $rt['section']); ?> | Room: <?php echo htmlspecialchars($rt['room_no']); ?></small>
                                        </div>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fs-6">
                                            <?php echo date('h:i A', strtotime($rt['start_time'])); ?> - <?php echo date('h:i A', strtotime($rt['end_time'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Action Links -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-bolt text-warning me-2"></i> Teacher Quick Hub</h6>
                    </div>
                    <div class="card-body d-flex flex-column gap-2">
                        <a href="<?php echo BASE_URL; ?>/teacher/attendance.php" class="btn btn-outline-primary text-start p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-clipboard-check me-2"></i> Take Class Attendance</div>
                                <small class="text-muted">Mark daily presence, absence, and late notes</small>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/teacher/assignments.php" class="btn btn-outline-primary text-start p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-file-pen me-2"></i> Manage Assignments & Grade</div>
                                <small class="text-muted">Review student submissions and award scores</small>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/teacher/study_materials.php" class="btn btn-outline-primary text-start p-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Study Materials</div>
                                <small class="text-muted">Share lecture slides, reference documents, and notes</small>
                            </div>
                            <i class="fa-solid fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
