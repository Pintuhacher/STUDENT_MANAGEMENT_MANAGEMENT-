<?php
$page_title = 'Exam Timetable & Schedule';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
$subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name ASC")->fetchAll();
$exams = $pdo->query("SELECT id, title, session_year FROM exams ORDER BY id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    validate_csrf();
    $exam_id = intval($_POST['exam_id'] ?? 0);
    $class_id = intval($_POST['class_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $exam_date = $_POST['exam_date'] ?? date('Y-m-d');
    $start_time = $_POST['start_time'] ?? '10:00:00';
    $end_time = $_POST['end_time'] ?? '13:00:00';
    $room_no = trim($_POST['room_no'] ?? 'Room 101');

    if ($exam_id > 0 && $class_id > 0 && $subject_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO exam_schedules (exam_id, class_id, subject_id, exam_date, start_time, end_time, room_no) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$exam_id, $class_id, $subject_id, $exam_date, $start_time, $end_time, $room_no]);
        log_activity($pdo, $_SESSION['user_id'], 'Schedule Exam', "Scheduled exam date $exam_date in $room_no");
        set_flash('success', 'Exam session scheduled successfully!');
        header('Location: ' . BASE_URL . '/teacher/exam_schedule.php');
        exit;
    }
}

$schedules = $pdo->query("SELECT es.*, e.title as exam_title, c.name as class_name, c.section, sub.name as subject_name 
                          FROM exam_schedules es 
                          JOIN exams e ON es.exam_id = e.id 
                          JOIN classes c ON es.class_id = c.id 
                          JOIN subjects sub ON es.subject_id = sub.id 
                          ORDER BY es.exam_date ASC, es.start_time ASC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Exam Timetable & Hall Schedules</h4>
                <p class="text-muted small mb-0">View and construct upcoming examination dates, timing, and room allocations.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newScheduleModal">
                <i class="fa-solid fa-plus me-1"></i> Add Exam Session
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exam Term</th>
                            <th>Class & Section</th>
                            <th>Subject</th>
                            <th>Exam Date</th>
                            <th>Time Window</th>
                            <th>Hall / Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $sc): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($sc['exam_title']); ?></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($sc['class_name'] . ' (' . $sc['section'] . ')'); ?></span></td>
                                <td class="fw-semibold text-dark"><?php echo htmlspecialchars($sc['subject_name']); ?></td>
                                <td><?php echo format_date($sc['exam_date']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo date('h:i A', strtotime($sc['start_time'])); ?> - <?php echo date('h:i A', strtotime($sc['end_time'])); ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($sc['room_no']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Schedule Modal -->
        <div class="modal fade" id="newScheduleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Schedule Exam Paper</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Examination *</label>
                                <select name="exam_id" class="form-select" required>
                                    <?php foreach ($exams as $e): ?>
                                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['title'] . ' (' . $e['session_year'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Class *</label>
                                    <select name="class_id" class="form-select" required>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Subject *</label>
                                    <select name="subject_id" class="form-select" required>
                                        <?php foreach ($subjects as $sub): ?>
                                            <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Exam Date</label>
                                    <input type="date" name="exam_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Hall / Room</label>
                                    <input type="text" name="room_no" class="form-control" required value="Hall A-101">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Start Time</label>
                                    <input type="time" name="start_time" class="form-control" value="10:00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">End Time</label>
                                    <input type="time" name="end_time" class="form-control" value="13:00">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_schedule" class="btn btn-primary btn-sm fw-semibold">Save Schedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
