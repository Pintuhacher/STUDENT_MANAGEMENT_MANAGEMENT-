<?php
$page_title = 'Marks & Results Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$exams = $pdo->query("SELECT * FROM exams ORDER BY id DESC")->fetchAll();
$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();

$selected_exam = intval($_GET['exam_id'] ?? ($exams[0]['id'] ?? 0));
$selected_class = intval($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));

$subjects = [];
$students = [];
$marks_matrix = [];

if ($selected_class > 0) {
    $s_stmt = $pdo->prepare("SELECT id, name, code, max_marks FROM subjects WHERE class_id = ? ORDER BY name ASC");
    $s_stmt->execute([$selected_class]);
    $subjects = $s_stmt->fetchAll();

    $st_stmt = $pdo->prepare("SELECT s.id, s.roll_no, u.full_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.class_id = ? AND s.status = 'active' ORDER BY s.roll_no ASC");
    $st_stmt->execute([$selected_class]);
    $students = $st_stmt->fetchAll();

    if ($selected_exam > 0) {
        $m_stmt = $pdo->prepare("SELECT student_id, subject_id, marks_obtained, grade FROM marks WHERE exam_id = ?");
        $m_stmt->execute([$selected_exam]);
        while ($row = $m_stmt->fetch()) {
            $marks_matrix[$row['student_id']][$row['subject_id']] = $row;
        }
    }
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-simple text-primary me-2"></i> Examination Marks & Grade Sheets</h4>
                <p class="text-muted small mb-0">View student scorecards, class-wide grade sheets, and assessment outcomes.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-print me-1"></i> Print Grade Sheet</button>
        </div>

        <div class="card border-0 shadow-sm p-3 mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Examination</label>
                    <select name="exam_id" class="form-select">
                        <?php foreach ($exams as $e): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo $selected_exam == $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['title'] . ' (' . $e['session_year'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Class</label>
                    <select name="class_id" class="form-select">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selected_class == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Load Marks</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Roll</th>
                            <th class="text-start">Student Name</th>
                            <?php foreach ($subjects as $sub): ?>
                                <th>
                                    <div><?php echo htmlspecialchars($sub['name']); ?></div>
                                    <small class="text-muted fw-normal">Max: <?php echo $sub['max_marks']; ?></small>
                                </th>
                            <?php endforeach; ?>
                            <th>Total Marks</th>
                            <th>Overall Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="<?php echo count($subjects) + 4; ?>" class="text-center py-4 text-muted">No students found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $stu): ?>
                                <?php
                                $total_obtained = 0;
                                $total_max = 0;
                                ?>
                                <tr>
                                    <td class="text-start"><strong><?php echo htmlspecialchars($stu['roll_no']); ?></strong></td>
                                    <td class="text-start fw-semibold text-dark"><?php echo htmlspecialchars($stu['full_name']); ?></td>
                                    <?php foreach ($subjects as $sub): ?>
                                        <?php
                                        $sub_mark = $marks_matrix[$stu['id']][$sub['id']] ?? null;
                                        if ($sub_mark) {
                                            $total_obtained += $sub_mark['marks_obtained'];
                                            $total_max += $sub['max_marks'];
                                        }
                                        ?>
                                        <td>
                                            <?php if ($sub_mark): ?>
                                                <span class="fw-bold"><?php echo $sub_mark['marks_obtained']; ?></span>
                                                <span class="badge bg-light text-dark border ms-1"><?php echo $sub_mark['grade']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class="fw-bold text-primary">
                                        <?php echo $total_obtained; ?> / <?php echo $total_max ?: 100; ?>
                                    </td>
                                    <td>
                                        <?php if ($total_max > 0): ?>
                                            <span class="badge bg-success fs-6"><?php echo calculate_grade($total_obtained, $total_max); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
