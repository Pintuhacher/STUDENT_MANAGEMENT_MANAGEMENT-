<?php
$page_title = 'Assignments & Marks';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_id = $stmt->fetchColumn() ?: 0;

// Create Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    validate_csrf();
    $class_id = intval($_POST['class_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'));
    $max_marks = intval($_POST['max_marks'] ?? 25);

    if ($class_id > 0 && $subject_id > 0 && !empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO assignments (class_id, subject_id, teacher_id, title, description, due_date, max_marks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $subject_id, $teacher_id, $title, $description, $due_date, $max_marks]);
        log_activity($pdo, $_SESSION['user_id'], 'Create Assignment', "Created assignment: $title");
        set_flash('success', 'Assignment published successfully!');
        header('Location: ' . BASE_URL . '/teacher/assignments.php');
        exit;
    }
}

// Grade Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    validate_csrf();
    $sub_id = intval($_POST['submission_id'] ?? 0);
    $marks = floatval($_POST['marks_obtained'] ?? 0);
    $feedback = trim($_POST['teacher_feedback'] ?? '');

    $stmt = $pdo->prepare("UPDATE assignment_submissions SET marks_obtained = ?, teacher_feedback = ?, status = 'graded' WHERE id = ?");
    $stmt->execute([$marks, $feedback, $sub_id]);
    set_flash('success', 'Marks and feedback saved!');
    header('Location: ' . BASE_URL . '/teacher/assignments.php');
    exit;
}

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
$subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name ASC")->fetchAll();

$assignments = $pdo->prepare("SELECT a.*, c.name as class_name, c.section, s.name as subject_name,
                                     (SELECT COUNT(*) FROM assignment_submissions sub WHERE sub.assignment_id = a.id) as submission_count 
                              FROM assignments a 
                              JOIN classes c ON a.class_id = c.id 
                              JOIN subjects s ON a.subject_id = s.id 
                              WHERE a.teacher_id = ? 
                              ORDER BY a.id DESC");
$assignments->execute([$teacher_id]);
$assignment_list = $assignments->fetchAll();

// Submissions for review
$submissions = $pdo->prepare("SELECT sub.*, a.title as assignment_title, a.max_marks, u.full_name as student_name, st.roll_no 
                              FROM assignment_submissions sub 
                              JOIN assignments a ON sub.assignment_id = a.id 
                              JOIN students st ON sub.student_id = st.id 
                              JOIN users u ON st.user_id = u.id 
                              WHERE a.teacher_id = ? 
                              ORDER BY sub.id DESC");
$submissions->execute([$teacher_id]);
$submission_list = $submissions->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-signature text-primary me-2"></i> Assignments & Grading Center</h4>
                <p class="text-muted small mb-0">Publish coursework, view student submissions, and assign performance scores.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                <i class="fa-solid fa-plus me-1"></i> Create Assignment
            </button>
        </div>

        <ul class="nav nav-tabs mb-4" id="assignTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#activeTab">My Assignments (<?php echo count($assignment_list); ?>)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#subTab">Student Submissions (<?php echo count($submission_list); ?>)</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Active Assignments List -->
            <div class="tab-pane fade show active" id="activeTab">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Class & Subject</th>
                                    <th>Due Date</th>
                                    <th>Max Marks</th>
                                    <th>Submissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assignment_list)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No assignments created yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($assignment_list as $a): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($a['description']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($a['class_name'] . ' ' . $a['section']); ?></span>
                                                <span class="badge bg-light text-dark border ms-1"><?php echo htmlspecialchars($a['subject_name']); ?></span>
                                            </td>
                                            <td><small class="text-muted"><?php echo format_date($a['due_date']); ?></small></td>
                                            <td><span class="badge bg-secondary"><?php echo $a['max_marks']; ?> Marks</span></td>
                                            <td><span class="badge bg-info text-white"><?php echo $a['submission_count']; ?> Submitted</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Student Submissions -->
            <div class="tab-pane fade" id="subTab">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Student Solution / Notes</th>
                                    <th>Status</th>
                                    <th>Marks Given</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($submission_list)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No student submissions received yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($submission_list as $sub): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($sub['student_name']); ?></div>
                                                <small class="text-muted">Roll: <?php echo htmlspecialchars($sub['roll_no']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($sub['assignment_title']); ?></td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($sub['student_notes'] ?? 'No notes'); ?></td>
                                            <td>
                                                <span class="badge <?php echo $sub['status'] === 'graded' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                    <?php echo ucfirst($sub['status']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold"><?php echo $sub['marks_obtained'] !== null ? $sub['marks_obtained'] . ' / ' . $sub['max_marks'] : '-'; ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gradeModal<?php echo $sub['id']; ?>">
                                                    <i class="fa-solid fa-pen-nib me-1"></i> Grade
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Grading Modal -->
                                        <div class="modal fade" id="gradeModal<?php echo $sub['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title fw-bold">Grade Student Submission</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted mb-2">Student: <strong><?php echo htmlspecialchars($sub['student_name']); ?></strong> | Max Marks: <strong><?php echo $sub['max_marks']; ?></strong></p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Marks Awarded *</label>
                                                                <input type="number" step="0.5" name="marks_obtained" class="form-control" required max="<?php echo $sub['max_marks']; ?>" value="<?php echo $sub['marks_obtained'] ?? ''; ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">Teacher Feedback</label>
                                                                <textarea name="teacher_feedback" class="form-control" rows="3"><?php echo htmlspecialchars($sub['teacher_feedback'] ?? ''); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="grade_submission" class="btn btn-success btn-sm fw-semibold">Submit Grade</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Assignment Modal -->
        <div class="modal fade" id="newAssignmentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Create New Assignment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Target Class *</label>
                                    <select name="class_id" class="form-select" required>
                                        <option value="">-- Choose Class --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Subject *</label>
                                    <select name="subject_id" class="form-select" required>
                                        <option value="">-- Choose Subject --</option>
                                        <?php foreach ($subjects as $sub): ?>
                                            <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name'] . ' (' . $sub['code'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Assignment Title *</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Relational Database Modeling Project">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Instructions & Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Provide problem statement or assignment instructions"></textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Max Marks</label>
                                    <input type="number" name="max_marks" class="form-control" value="25">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Due Date</label>
                                    <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="create_assignment" class="btn btn-primary btn-sm fw-semibold">Publish Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
