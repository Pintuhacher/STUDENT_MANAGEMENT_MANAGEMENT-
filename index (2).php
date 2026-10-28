<?php
$page_title = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

// Student record
$stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email, u.phone, c.name as class_name, c.section 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN classes c ON s.class_id = c.id 
                       WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'] ?? 0;
$class_id = $student['class_id'] ?? 0;

// Attendance Percentage
$att_total = 0;
$att_present = 0;
$att_percent = 100;
if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, 
                                  SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_count 
                           FROM attendance WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $att = $stmt->fetch();
    $att_total = $att['total'] ?? 0;
    $att_present = $att['present_count'] ?? 0;
    $att_percent = $att_total > 0 ? round(($att_present / $att_total) * 100) : 100;
}

// Pending Assignments
$pending_assignments = 0;
if ($class_id > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments a 
                           WHERE a.class_id = ? AND a.id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)");
    $stmt->execute([$class_id, $student_id]);
    $pending_assignments = $stmt->fetchColumn();
}

// Fees Balance
$fee_balance = 0;
if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount - paid_amount), 0) FROM fees WHERE student_id = ? AND status != 'paid'");
    $stmt->execute([$student_id]);
    $fee_balance = $stmt->fetchColumn();
}

// Latest Notices
$notices = $pdo->query("SELECT * FROM notices WHERE target_audience IN ('all', 'students') ORDER BY id DESC LIMIT 3")->fetchAll();

// Today's Routine
$today_day = date('l');
$routine = [];
if ($class_id > 0) {
    $stmt = $pdo->prepare("SELECT r.*, sub.name as subject_name, u.full_name as teacher_name 
                           FROM class_routines r 
                           JOIN subjects sub ON r.subject_id = sub.id 
                           LEFT JOIN teachers t ON r.teacher_id = t.id 
                           LEFT JOIN users u ON t.user_id = u.id 
                           WHERE r.class_id = ? AND r.day_of_week = ? 
                           ORDER BY r.start_time ASC");
    $stmt->execute([$class_id, $today_day]);
    $routine = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Welcome, <?php echo htmlspecialchars($student['full_name'] ?? $_SESSION['user_name']); ?>!</h4>
                <p class="text-muted small mb-0">Class: <strong><?php echo htmlspecialchars(($student['class_name'] ?? 'Class') . ' ' . ($student['section'] ?? '')); ?></strong> | Roll No: <strong><?php echo htmlspecialchars($student['roll_no'] ?? '-'); ?></strong> | Admission No: <strong><?php echo htmlspecialchars($student['admission_no'] ?? '-'); ?></strong></p>
            </div>
            <a href="<?php echo BASE_URL; ?>/student/profile.php" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-id-card me-1"></i> My Profile</a>
        </div>

        <!-- Metric Badges -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Attendance Rate</span>
                            <h3 class="fw-bold mb-0 mt-1"><?php echo $att_percent; ?>%</h3>
                            <span class="badge <?php echo $att_percent >= 75 ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10 <?php echo $att_percent >= 75 ? 'text-success' : 'text-danger'; ?> mt-1 small">
                                <?php echo $att_present; ?> / <?php echo $att_total; ?> Days Present
                            </span>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-chart-pie"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Pending Tasks</span>
                            <h3 class="fw-bold mb-0 mt-1"><?php echo $pending_assignments; ?></h3>
                            <a href="<?php echo BASE_URL; ?>/student/assignments.php" class="badge bg-primary bg-opacity-10 text-primary mt-1 small text-decoration-none">Submit Solutions</a>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa-solid fa-book-bookmark"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Outstanding Fees</span>
                            <h3 class="fw-bold mb-0 mt-1">₹<?php echo number_format($fee_balance, 2); ?></h3>
                            <span class="badge <?php echo $fee_balance == 0 ? 'bg-success' : 'bg-warning'; ?> bg-opacity-10 <?php echo $fee_balance == 0 ? 'text-success' : 'text-warning'; ?> mt-1 small">
                                <?php echo $fee_balance == 0 ? 'All Clear' : 'Payment Due'; ?>
                            </span>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-wallet"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card stat-card p-3 border-0 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-medium">Exam Performance</span>
                            <h3 class="fw-bold mb-0 mt-1">Grade A+</h3>
                            <a href="<?php echo BASE_URL; ?>/student/results.php" class="badge bg-info bg-opacity-10 text-info mt-1 small text-decoration-none">View Marksheet</a>
                        </div>
                        <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fa-solid fa-award"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Today's Routine -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-calendar-day text-primary me-2"></i> Today's Class Routine (<?php echo $today_day; ?>)</h6>
                        <a href="<?php echo BASE_URL; ?>/student/routine.php" class="small text-decoration-none">Full Week Timetable</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($routine)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fa-solid fa-calendar-xmark fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                No classes scheduled for today.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($routine as $rt): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                                        <div>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($rt['subject_name']); ?></div>
                                            <small class="text-muted"><i class="fa-solid fa-user-tie me-1"></i> <?php echo htmlspecialchars($rt['teacher_name'] ?? 'Faculty'); ?> | Room: <?php echo htmlspecialchars($rt['room_no']); ?></small>
                                        </div>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo date('h:i A', strtotime($rt['start_time'])); ?> - <?php echo date('h:i A', strtotime($rt['end_time'])); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notice Board Preview -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold"><i class="fa-solid fa-bullhorn text-danger me-2"></i> Institutional Notice Board</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($notices as $n): ?>
                                <div class="list-group-item p-3 border-0 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge <?php echo $n['priority'] === 'urgent' ? 'bg-danger' : 'bg-primary'; ?> text-uppercase" style="font-size:0.65rem;"><?php echo $n['priority']; ?></span>
                                        <small class="text-muted"><?php echo format_date($n['created_at']); ?></small>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($n['title']); ?></h6>
                                    <p class="small text-muted mb-0"><?php echo htmlspecialchars(substr($n['content'], 0, 100)) . '...'; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
