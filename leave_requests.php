<?php
$page_title = 'Leave Requests';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_id = $stmt->fetchColumn() ?: 0;

$action = $_GET['action'] ?? '';
$leave_id = intval($_GET['id'] ?? 0);

if ($action && in_array($action, ['approve', 'reject']) && $leave_id > 0) {
    validate_csrf();
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $remarks = trim($_POST['remarks'] ?? ($action === 'approve' ? 'Approved by Class Teacher' : 'Rejected'));

    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ?, teacher_remarks = ?, reviewed_by = ? WHERE id = ?");
    $stmt->execute([$status, $remarks, $teacher_id, $leave_id]);

    log_activity($pdo, $_SESSION['user_id'], 'Review Leave', "Marked leave ID $leave_id as $status");
    set_flash('success', "Leave request $status.");
    header('Location: ' . BASE_URL . '/teacher/leave_requests.php');
    exit;
}

$leaves = $pdo->query("SELECT lr.*, u.full_name as student_name, st.roll_no, c.name as class_name, c.section 
                       FROM leave_requests lr 
                       JOIN students st ON lr.student_id = st.id 
                       JOIN users u ON st.user_id = u.id 
                       LEFT JOIN classes c ON st.class_id = c.id 
                       ORDER BY lr.id DESC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i> Student Leave Applications</h4>
                <p class="text-muted small mb-0">Review student absence requests and record approval decisions.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Date Range</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaves)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No student leave applications submitted.</td></tr>
                        <?php else: ?>
                            <?php foreach ($leaves as $l): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($l['student_name']); ?></div>
                                        <small class="text-muted">Roll: <?php echo htmlspecialchars($l['roll_no']); ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($l['class_name'] . ' ' . $l['section']); ?></span></td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo format_date($l['start_date']); ?> to <?php echo format_date($l['end_date']); ?></div>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($l['reason']); ?></td>
                                    <td>
                                        <?php if ($l['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($l['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($l['status'] === 'pending'): ?>
                                            <form method="POST" action="?action=approve&id=<?php echo $l['id']; ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                            <form method="POST" action="?action=reject&id=<?php echo $l['id']; ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-xmark"></i></button>
                                            </form>
                                        <?php else: ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($l['teacher_remarks'] ?: 'Done'); ?></small>
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
