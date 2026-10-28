<?php
$page_title = 'Exam Results & Marksheet';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT s.*, u.full_name, c.name as class_name, c.section 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN classes c ON s.class_id = c.id 
                       WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'] ?? 0;

$exams = $pdo->query("SELECT * FROM exams ORDER BY id DESC")->fetchAll();
$selected_exam = intval($_GET['exam_id'] ?? ($exams[0]['id'] ?? 0));

$results = [];
if ($student_id > 0 && $selected_exam > 0) {
    $stmt = $pdo->prepare("SELECT m.*, sub.name as subject_name, sub.code as subject_code, sub.max_marks 
                           FROM marks m 
                           JOIN subjects sub ON m.subject_id = sub.id 
                           WHERE m.student_id = ? AND m.exam_id = ? 
                           ORDER BY sub.name ASC");
    $stmt->execute([$student_id, $selected_exam]);
    $results = $stmt->fetchAll();
}

$total_obtained = 0;
$total_max = 0;
foreach ($results as $res) {
    $total_obtained += $res['marks_obtained'];
    $total_max += $res['max_marks'];
}
$overall_percent = $total_max > 0 ? round(($total_obtained / $total_max) * 100, 2) : 0;
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-award text-primary me-2"></i> Examination Marksheet & Report Card</h4>
                <p class="text-muted small mb-0">Official academic scorecards and evaluation certificates.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-print me-1"></i> Print Marksheet</button>
        </div>

        <div class="card border-0 shadow-sm p-3 mb-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Select Examination</label>
                    <select name="exam_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($exams as $e): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo $selected_exam == $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['title'] . ' (' . $e['session_year'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm p-4">
            <div class="row align-items-center mb-4 pb-3 border-bottom">
                <div class="col-md-8">
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                    <p class="small text-muted mb-0">Class: <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?> | Roll: <?php echo htmlspecialchars($student['roll_no']); ?> | Admission: <?php echo htmlspecialchars($student['admission_no']); ?></p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="badge bg-primary fs-6 px-3 py-2">Overall: <?php echo calculate_grade($total_obtained, $total_max); ?> (<?php echo $overall_percent; ?>%)</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Subject Name</th>
                            <th>Code</th>
                            <th>Max Marks</th>
                            <th>Marks Obtained</th>
                            <th>Grade</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($results)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No marks published for this examination yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($results as $res): ?>
                                <tr>
                                    <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($res['subject_name']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($res['subject_code']); ?></span></td>
                                    <td><?php echo $res['max_marks']; ?></td>
                                    <td class="fw-bold text-primary"><?php echo $res['marks_obtained']; ?></td>
                                    <td><span class="badge bg-success fs-6"><?php echo $res['grade']; ?></span></td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($res['remarks'] ?? 'Good effort'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-light fw-bold">
                                <td colspan="2" class="text-start">Grand Total</td>
                                <td><?php echo $total_max; ?></td>
                                <td class="text-primary"><?php echo $total_obtained; ?></td>
                                <td><?php echo calculate_grade($total_obtained, $total_max); ?></td>
                                <td>Result: PASS</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
