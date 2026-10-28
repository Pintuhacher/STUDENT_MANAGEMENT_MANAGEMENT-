<?php
$page_title = 'Apply for Leave';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn() ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    validate_csrf();
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d');
    $reason = trim($_POST['reason'] ?? '');

    if ($student_id > 0 && !empty($reason)) {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (student_id, start_date, end_date, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $start_date, $end_date, $reason]);
        log_activity($pdo, $_SESSION['user_id'], 'Apply Leave', "Applied for leave from $start_date to $end_date");
        set_flash('success', 'Leave application submitted to your class teacher for approval.');
        header('Location: ' . BASE_URL . '/student/leave_request.php');
        exit;
    }
}

$leaves = [];
if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE student_id = ? ORDER BY id DESC");
    $stmt->execute([$student_id]);
    $leaves = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="mb-4">
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-plane-departure text-primary me-2"></i> Student Leave Application</h4>
            <p class="text-muted small mb-0">Apply for planned leaves and check the status of teacher approvals.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4">
                    <h6 class="fw-bold text-primary mb-3">Submit Leave Request</h6>
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">End Date *</label>
                                <input type="date" name="end_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Reason for Absence *</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Specify reason: medical sickness, family obligation, competition, etc."></textarea>
                        </div>
                        <button type="submit" name="apply_leave" class="btn btn-primary w-100 fw-semibold">Send Leave Application</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold">My Leave History</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Dates</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Teacher Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaves)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No past leave applications found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($leaves as $l): ?>
                                        <tr>
                                            <td>
                                                <div class="small fw-bold"><?php echo format_date($l['start_date']); ?></div>
                                                <div class="small text-muted">to <?php echo format_date($l['end_date']); ?></div>
                                            </td>
                                            <td class="small"><?php echo htmlspecialchars($l['reason']); ?></td>
                                            <td>
                                                <?php if ($l['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php elseif ($l['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($l['teacher_remarks'] ?: 'Pending Review'); ?></td>
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
