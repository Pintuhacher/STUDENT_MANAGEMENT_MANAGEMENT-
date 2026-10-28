<?php
$page_title = 'Take Student Attendance';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_id = $stmt->fetchColumn() ?: 0;

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
$selected_class = intval($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Save Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    validate_csrf();
    $statuses = $_POST['attendance'] ?? [];
    $remarks = $_POST['remarks'] ?? [];

    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, class_id, date, status, marked_by, remarks) 
                           VALUES (?, ?, ?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)");

    foreach ($statuses as $stu_id => $st) {
        $rem = trim($remarks[$stu_id] ?? '');
        $stmt->execute([$stu_id, $selected_class, $selected_date, $st, $teacher_id, $rem]);
    }

    log_activity($pdo, $_SESSION['user_id'], 'Take Attendance', "Marked attendance for class ID: $selected_class on $selected_date");
    set_flash('success', 'Class attendance saved successfully!');
    header('Location: ' . BASE_URL . "/teacher/attendance.php?class_id=$selected_class&date=$selected_date");
    exit;
}

$students = [];
if ($selected_class > 0) {
    $stmt = $pdo->prepare("SELECT s.id, s.admission_no, s.roll_no, u.full_name,
                                  a.status as current_status, a.remarks as current_remarks 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ? 
                           WHERE s.class_id = ? AND s.status = 'active' 
                           ORDER BY s.roll_no ASC");
    $stmt->execute([$selected_date, $selected_class]);
    $students = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-clipboard-user text-primary me-2"></i> Daily Student Attendance</h4>
                <p class="text-muted small mb-0">Record and submit class attendance with 1-click presets.</p>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-success btn-sm" onclick="markAll('present')"><i class="fa-solid fa-check-double me-1"></i> All Present</button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="markAll('absent')">All Absent</button>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-3 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Class & Section</label>
                    <select name="class_id" class="form-select">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selected_class == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Attendance Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-calendar-day me-1"></i> Select Date</button>
                </div>
            </form>
        </div>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Roll</th>
                                <th>Student Name</th>
                                <th>Attendance Status</th>
                                <th>Remarks / Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No students found in this class.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <?php $st = $s['current_status'] ?: 'present'; ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($s['roll_no']); ?></strong></td>
                                        <td class="fw-semibold text-dark"><?php echo htmlspecialchars($s['full_name']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <input type="radio" class="btn-check att-radio-present" name="attendance[<?php echo $s['id']; ?>]" id="p_<?php echo $s['id']; ?>" value="present" <?php echo $st === 'present' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success" for="p_<?php echo $s['id']; ?>"><i class="fa-solid fa-check"></i> Present</label>

                                                <input type="radio" class="btn-check att-radio-absent" name="attendance[<?php echo $s['id']; ?>]" id="a_<?php echo $s['id']; ?>" value="absent" <?php echo $st === 'absent' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-danger" for="a_<?php echo $s['id']; ?>"><i class="fa-solid fa-xmark"></i> Absent</label>

                                                <input type="radio" class="btn-check" name="attendance[<?php echo $s['id']; ?>]" id="l_<?php echo $s['id']; ?>" value="late" <?php echo $st === 'late' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning" for="l_<?php echo $s['id']; ?>"><i class="fa-solid fa-clock"></i> Late</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="remarks[<?php echo $s['id']; ?>]" class="form-control form-control-sm" placeholder="Optional notes" value="<?php echo htmlspecialchars($s['current_remarks'] ?? ''); ?>" style="max-width: 250px;">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (!empty($students)): ?>
                    <div class="card-footer bg-white p-3 text-end">
                        <button type="submit" name="save_attendance" class="btn btn-primary fw-semibold px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Class Attendance
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </main>

<script>
function markAll(status) {
    if (status === 'present') {
        document.querySelectorAll('.att-radio-present').forEach(el => el.checked = true);
    } else if (status === 'absent') {
        document.querySelectorAll('.att-radio-absent').forEach(el => el.checked = true);
    }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
