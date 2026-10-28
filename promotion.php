<?php
$page_title = 'Class Promotion System';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();

$source_class_id = intval($_GET['source_class_id'] ?? 0);
$target_class_id = intval($_POST['target_class_id'] ?? 0);

// Process Promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_students'])) {
    validate_csrf();
    $student_ids = $_POST['student_ids'] ?? [];
    if (!empty($student_ids) && $target_class_id > 0) {
        $in_clause = implode(',', array_fill(0, count($student_ids), '?'));
        $params = array_merge([$target_class_id], $student_ids);
        $stmt = $pdo->prepare("UPDATE students SET class_id = ? WHERE id IN ($in_clause)");
        $stmt->execute($params);

        $promoted_count = count($student_ids);
        log_activity($pdo, $_SESSION['user_id'], 'Class Promotion', "Promoted $promoted_count students to class ID: $target_class_id");
        set_flash('success', "Successfully promoted $promoted_count student(s) to the selected target class!");
        header('Location: ' . BASE_URL . '/admin/promotion.php?source_class_id=' . $target_class_id);
        exit;
    } else {
        set_flash('warning', 'Please select at least one student and a target promotion class.');
    }
}

// Fetch source class students
$source_students = [];
if ($source_class_id > 0) {
    $stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.class_id = ? AND s.status = 'active' ORDER BY s.roll_no ASC");
    $stmt->execute([$source_class_id]);
    $source_students = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="mb-4">
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-arrow-trend-up text-primary me-2"></i> Class Promotion Engine</h4>
            <p class="text-muted small mb-0">Promote cohorts of students from their current academic class to the subsequent grade or section.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-4">
                    <h6 class="fw-bold text-primary mb-3">Step 1: Choose Source Class</h6>
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Current Enrolled Class</label>
                            <select name="source_class_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Choose Class --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $source_class_id == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <div class="alert alert-info small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Selecting a class loads all active students. You can review and selectively check off students to promote to the next grade.
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <?php if ($source_class_id > 0): ?>
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">Step 2: Select Students & Target Class</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label small fw-bold mb-0 text-nowrap">Promote To:</label>
                                    <select name="target_class_id" class="form-select form-select-sm" required style="width: 220px;">
                                        <option value="">-- Select Target Class --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <?php if ($c['id'] != $source_class_id): ?>
                                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40"><input type="checkbox" id="selectAll" class="form-check-input" onclick="toggleAll(this)"></th>
                                            <th>Roll No</th>
                                            <th>Student Name</th>
                                            <th>Admission No</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($source_students)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">No active students found in this class.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($source_students as $s): ?>
                                                <tr>
                                                    <td><input type="checkbox" name="student_ids[]" value="<?php echo $s['id']; ?>" class="form-check-input student-check" checked></td>
                                                    <td><strong><?php echo htmlspecialchars($s['roll_no']); ?></strong></td>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($s['email']); ?></small>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['admission_no']); ?></span></td>
                                                    <td><span class="badge bg-success">Active</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($source_students)): ?>
                                <div class="card-footer bg-white p-3 text-end">
                                    <button type="submit" name="promote_students" class="btn btn-success fw-semibold" onclick="return confirm('Confirm promotion of selected students to the target class?');">
                                        <i class="fa-solid fa-graduation-cap me-1"></i> Confirm & Promote Selected Students
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="card border-0 shadow-sm p-5 text-center text-muted">
                        <i class="fa-solid fa-arrow-trend-up fa-3x text-secondary opacity-50 mb-3"></i>
                        <h5>Select a class on the left to start promotion</h5>
                        <p class="small">You will be able to review the student roster and advance them into the new academic year.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

<script>
function toggleAll(master) {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = master.checked);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
