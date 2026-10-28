<?php
$page_title = 'Student Registration Approvals';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$action = $_GET['action'] ?? '';
$user_id = intval($_GET['id'] ?? 0);

if ($action === 'approve' && $user_id > 0) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'student'");
    $stmt->execute([$user_id]);
    $stmt2 = $pdo->prepare("UPDATE students SET status = 'active' WHERE user_id = ?");
    $stmt2->execute([$user_id]);
    log_activity($pdo, $_SESSION['user_id'], 'Approve Student', "Approved student user ID: $user_id");
    set_flash('success', 'Student registration approved successfully! The student can now log in.');
    header('Location: ' . BASE_URL . '/admin/student_approve.php');
    exit;
} elseif ($action === 'reject' && $user_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student' AND status = 'pending'");
    $stmt->execute([$user_id]);
    log_activity($pdo, $_SESSION['user_id'], 'Reject Student', "Rejected pending student user ID: $user_id");
    set_flash('danger', 'Student registration was rejected and removed.');
    header('Location: ' . BASE_URL . '/admin/student_approve.php');
    exit;
}

$pending_students = $pdo->query("SELECT u.id, u.username, u.full_name, u.email, u.phone, u.created_at, 
                                        s.admission_no, s.roll_no, s.dob, s.gender, s.father_name, s.parent_phone, s.address,
                                        c.name as class_name, c.section 
                                 FROM users u 
                                 JOIN students s ON u.id = s.user_id 
                                 LEFT JOIN classes c ON s.class_id = c.id 
                                 WHERE u.role = 'student' AND u.status = 'pending' 
                                 ORDER BY u.id DESC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-check text-primary me-2"></i> Student Registration Approval Queue</h4>
                <p class="text-muted small mb-0">Review and verify applications submitted via the student self-registration portal.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/students.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i> Active Students List</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Applied Class</th>
                            <th>Credentials</th>
                            <th>Guardian Details</th>
                            <th>Applied Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_students)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-inbox fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                    <h5>No Pending Registrations</h5>
                                    <p class="small">When students register on the portal, their records appear here for your review and approval.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_students as $s): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                        <div class="small text-muted">Gender: <?php echo htmlspecialchars($s['gender']); ?> | DOB: <?php echo format_date($s['dob']); ?></div>
                                        <div class="small text-muted">Address: <?php echo htmlspecialchars($s['address']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary fs-6">
                                            <?php echo htmlspecialchars(($s['class_name'] ?? 'Class') . ' - ' . ($s['section'] ?? '')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small"><strong>User:</strong> <?php echo htmlspecialchars($s['username']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($s['email']); ?></div>
                                        <div class="small text-muted">Phone: <?php echo htmlspecialchars($s['phone'] ?: '-'); ?></div>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo htmlspecialchars($s['father_name']); ?></div>
                                        <div class="small text-muted">Phone: <?php echo htmlspecialchars($s['parent_phone']); ?></div>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?php echo format_date($s['created_at']); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="?action=approve&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-success fw-semibold"><i class="fa-solid fa-check me-1"></i> Approve</a>
                                            <a href="?action=reject&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger fw-semibold" onclick="return confirm('Are you sure you want to reject and delete this registration application?');"><i class="fa-solid fa-xmark me-1"></i> Reject</a>
                                        </div>
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
