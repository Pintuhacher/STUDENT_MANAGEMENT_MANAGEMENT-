<?php
$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT s.*, u.username, u.full_name, u.email, u.phone, c.name as class_name, c.section 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       LEFT JOIN classes c ON s.class_id = c.id 
                       WHERE s.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$student_id = $student['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_update'])) {
    validate_csrf();
    $changes = trim($_POST['requested_changes'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (!empty($changes) && !empty($reason)) {
        $stmt = $pdo->prepare("INSERT INTO student_update_requests (student_id, requested_changes, reason) VALUES (?, ?, ?)");
        $stmt->execute([$student_id, $changes, $reason]);
        log_activity($pdo, $_SESSION['user_id'], 'Profile Update Request', "Requested changes for review");
        set_flash('success', 'Profile update request submitted to Administrator for verification!');
        header('Location: ' . BASE_URL . '/student/profile.php');
        exit;
    }
}

// Past requests
$past_requests = $pdo->prepare("SELECT * FROM student_update_requests WHERE student_id = ? ORDER BY id DESC");
$past_requests->execute([$student_id]);
$req_list = $past_requests->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="mb-4">
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-id-card text-primary me-2"></i> Student Academic Identity</h4>
            <p class="text-muted small mb-0">Official student record registered in the institutional repository.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fs-2" style="width:70px; height:70px;">
                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h5>
                            <span class="badge bg-primary bg-opacity-10 text-primary fs-6"><?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></span>
                            <span class="badge bg-light text-dark border ms-1">Roll: <?php echo htmlspecialchars($student['roll_no']); ?></span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Admission Number</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['admission_no']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Admission Date</span>
                            <span class="fw-bold"><?php echo format_date($student['admission_date']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Date of Birth</span>
                            <span class="fw-bold"><?php echo format_date($student['dob']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Gender & Blood Group</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['gender']); ?> (<?php echo htmlspecialchars($student['blood_group'] ?? 'B+'); ?>)</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Student Email</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Student Phone</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['phone'] ?: '-'); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Father's Name</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['father_name']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Mother's Name</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['mother_name']); ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Parent Contact</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['parent_phone']); ?></span>
                        </div>
                        <div class="col-sm-12">
                            <span class="text-muted small d-block">Permanent Address</span>
                            <span class="fw-bold"><?php echo htmlspecialchars($student['address']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Update Request Form -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa-solid fa-pen-to-square me-1"></i> Request Information Update</h6>
                    <p class="small text-muted">To maintain academic record integrity, profile updates (contact, address, guardian) are verified by the administration before being applied.</p>
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Requested Modifications *</label>
                            <textarea name="requested_changes" class="form-control" rows="3" required placeholder="e.g. Please update parent phone number to 9832100099 and update address..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Reason for Change *</label>
                            <input type="text" name="reason" class="form-control" required placeholder="e.g. Relocated to new residence / changed SIM card">
                        </div>
                        <button type="submit" name="request_update" class="btn btn-primary w-100 fw-semibold">Submit Update Request</button>
                    </form>

                    <?php if (!empty($req_list)): ?>
                        <hr class="my-4">
                        <h6 class="fw-bold small text-secondary">Past Requests</h6>
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($req_list as $rq): ?>
                                <li class="list-group-item px-0 py-2 border-0 border-bottom">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge <?php echo $rq['status'] === 'approved' ? 'bg-success' : ($rq['status'] === 'rejected' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo ucfirst($rq['status']); ?></span>
                                        <span class="text-muted"><?php echo format_date($rq['created_at']); ?></span>
                                    </div>
                                    <p class="mb-0 text-muted mt-1"><?php echo htmlspecialchars($rq['requested_changes']); ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
