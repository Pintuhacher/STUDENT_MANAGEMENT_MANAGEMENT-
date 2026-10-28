<?php
$page_title = 'Submit Assignments';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT s.id, s.class_id FROM students s WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'] ?? 0;
$class_id = $student['class_id'] ?? 0;

// Submit Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_solution'])) {
    validate_csrf();
    $assignment_id = intval($_POST['assignment_id'] ?? 0);
    $notes = trim($_POST['student_notes'] ?? '');
    $file_url = trim($_POST['file_url'] ?? '');

    if ($assignment_id > 0 && (!empty($notes) || !empty($file_url))) {
        $stmt = $pdo->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, student_notes, file_url, status) 
                               VALUES (?, ?, ?, ?, 'submitted') 
                               ON DUPLICATE KEY UPDATE student_notes = VALUES(student_notes), file_url = VALUES(file_url), submitted_at = NOW()");
        $stmt->execute([$assignment_id, $student_id, $notes, $file_url]);
        log_activity($pdo, $_SESSION['user_id'], 'Submit Assignment', "Submitted solution for assignment ID: $assignment_id");
        set_flash('success', 'Assignment solution submitted successfully!');
        header('Location: ' . BASE_URL . '/student/assignments.php');
        exit;
    }
}

$assignments = [];
if ($class_id > 0) {
    $stmt = $pdo->prepare("SELECT a.*, sub.name as subject_name, u.full_name as teacher_name, 
                                  asub.status as submission_status, asub.marks_obtained, asub.teacher_feedback, asub.submitted_at 
                           FROM assignments a 
                           JOIN subjects sub ON a.subject_id = sub.id 
                           JOIN teachers t ON a.teacher_id = t.id 
                           JOIN users u ON t.user_id = u.id 
                           LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ? 
                           WHERE a.class_id = ? 
                           ORDER BY a.due_date ASC");
    $stmt->execute([$student_id, $class_id]);
    $assignments = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-arrow-up text-primary me-2"></i> Course Assignments & Submissions</h4>
                <p class="text-muted small mb-0">View assignment briefs, submit your coursework, and check teacher feedback.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Assignment Title</th>
                            <th>Subject</th>
                            <th>Deadline</th>
                            <th>Max Marks</th>
                            <th>My Status</th>
                            <th>Grade</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignments)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No assignments assigned to your class.</td></tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $a): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['title']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($a['description']); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($a['subject_name']); ?></span></td>
                                    <td><small class="text-muted"><?php echo format_date($a['due_date']); ?></small></td>
                                    <td><?php echo $a['max_marks']; ?></td>
                                    <td>
                                        <?php if ($a['submission_status'] === 'graded'): ?>
                                            <span class="badge bg-success">Graded</span>
                                        <?php elseif ($a['submission_status'] === 'submitted'): ?>
                                            <span class="badge bg-info text-white">Submitted</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['marks_obtained'] !== null): ?>
                                            <span class="fw-bold text-success"><?php echo $a['marks_obtained']; ?> / <?php echo $a['max_marks']; ?></span>
                                            <div class="small text-muted" style="font-size:0.75rem;"><?php echo htmlspecialchars($a['teacher_feedback'] ?? ''); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#submitModal<?php echo $a['id']; ?>">
                                            <i class="fa-solid fa-cloud-arrow-up me-1"></i> <?php echo $a['submission_status'] ? 'Resubmit' : 'Submit'; ?>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Submission Modal -->
                                <div class="modal fade" id="submitModal<?php echo $a['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Submit Solution: <?php echo htmlspecialchars($a['title']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-2">Subject: <strong><?php echo htmlspecialchars($a['subject_name']); ?></strong> | Max Marks: <strong><?php echo $a['max_marks']; ?></strong></p>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Solution Text / Answers</label>
                                                        <textarea name="student_notes" class="form-control" rows="4" placeholder="Type your answers, code snippets, or notes..."></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Cloud Link or Document URL (Optional)</label>
                                                        <input type="url" name="file_url" class="form-control" placeholder="https://drive.google.com/... or Github link">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="submit_solution" class="btn btn-primary btn-sm fw-semibold">Upload Solution</button>
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
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
