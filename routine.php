<?php
$page_title = 'Class & Exam Routine';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT s.class_id, c.name as class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stu = $stmt->fetch();
$class_id = $stu['class_id'] ?? 0;

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$routine_by_day = [];

if ($class_id > 0) {
    $stmt = $pdo->prepare("SELECT r.*, sub.name as subject_name, sub.code, u.full_name as teacher_name 
                           FROM class_routines r 
                           JOIN subjects sub ON r.subject_id = sub.id 
                           LEFT JOIN teachers t ON r.teacher_id = t.id 
                           LEFT JOIN users u ON t.user_id = u.id 
                           WHERE r.class_id = ? 
                           ORDER BY r.start_time ASC");
    $stmt->execute([$class_id]);
    while ($row = $stmt->fetch()) {
        $routine_by_day[$row['day_of_week']][] = $row;
    }
}

// Exam Timetable
$exam_schedules = [];
if ($class_id > 0) {
    $stmt = $pdo->prepare("SELECT es.*, e.title as exam_title, sub.name as subject_name 
                           FROM exam_schedules es 
                           JOIN exams e ON es.exam_id = e.id 
                           JOIN subjects sub ON es.subject_id = sub.id 
                           WHERE es.class_id = ? 
                           ORDER BY es.exam_date ASC");
    $stmt->execute([$class_id]);
    $exam_schedules = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-table-cells text-primary me-2"></i> Weekly Class Routine & Exam Timetable</h4>
                <p class="text-muted small mb-0">Class: <strong><?php echo htmlspecialchars(($stu['class_name'] ?? 'Class') . ' ' . ($stu['section'] ?? '')); ?></strong></p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-print me-1"></i> Print Timetable</button>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#weeklyTab">Weekly Class Timetable</a></li>
            <li class="nav-item"><a class="nav-link fw-semibold" data-bs-toggle="tab" href="#examTab">Upcoming Exam Schedules</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="weeklyTab">
                <div class="row g-4">
                    <?php foreach ($days as $day): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-primary text-white py-2 fw-bold">
                                    <i class="fa-regular fa-calendar me-1"></i> <?php echo $day; ?>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($routine_by_day[$day])): ?>
                                        <div class="p-3 text-center text-muted small">No scheduled lectures.</div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($routine_by_day[$day] as $r): ?>
                                                <div class="list-group-item p-3 border-0 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge bg-light text-primary border"><?php echo date('h:i A', strtotime($r['start_time'])); ?> - <?php echo date('h:i A', strtotime($r['end_time'])); ?></span>
                                                        <small class="text-muted"><?php echo htmlspecialchars($r['room_no']); ?></small>
                                                    </div>
                                                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($r['subject_name']); ?></h6>
                                                    <small class="text-muted"><i class="fa-solid fa-user-tie me-1"></i> <?php echo htmlspecialchars($r['teacher_name'] ?? 'Faculty'); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="examTab">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Exam Term</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Hall / Room</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exam_schedules)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No examination timetable announced for your class.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($exam_schedules as $es): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($es['exam_title']); ?></td>
                                            <td><span class="badge bg-primary bg-opacity-10 text-primary fs-6"><?php echo htmlspecialchars($es['subject_name']); ?></span></td>
                                            <td><strong><?php echo format_date($es['exam_date'], 'l, d M Y'); ?></strong></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo date('h:i A', strtotime($es['start_time'])); ?> - <?php echo date('h:i A', strtotime($es['end_time'])); ?></span></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($es['room_no']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
