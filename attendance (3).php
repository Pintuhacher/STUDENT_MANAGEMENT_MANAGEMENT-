<?php
$page_title = 'Attendance Record';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn() ?: 0;

$records = [];
if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY date DESC");
    $stmt->execute([$student_id]);
    $records = $stmt->fetchAll();
}

$present_count = 0;
$absent_count = 0;
$late_count = 0;
foreach ($records as $r) {
    if ($r['status'] === 'present') $present_count++;
    elseif ($r['status'] === 'absent') $absent_count++;
    elseif ($r['status'] === 'late') $late_count++;
}
$total_days = count($records);
$percent = $total_days > 0 ? round((($present_count + $late_count) / $total_days) * 100) : 100;
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Attendance Record & Ledger</h4>
                <p class="text-muted small mb-0">Track your daily presence, absences, and attendance percentage.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white text-center">
                    <span class="text-muted small">Total Working Days</span>
                    <h3 class="fw-bold mb-0 text-dark"><?php echo $total_days; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white text-center">
                    <span class="text-muted small">Days Present</span>
                    <h3 class="fw-bold mb-0 text-success"><?php echo $present_count; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white text-center">
                    <span class="text-muted small">Days Absent</span>
                    <h3 class="fw-bold mb-0 text-danger"><?php echo $absent_count; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm p-3 bg-white text-center">
                    <span class="text-muted small">Overall Attendance</span>
                    <h3 class="fw-bold mb-0 <?php echo $percent >= 75 ? 'text-primary' : 'text-danger'; ?>"><?php echo $percent; ?>%</h3>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Teacher Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No attendance entries recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td><strong><?php echo format_date($r['date'], 'l, d M Y'); ?></strong></td>
                                    <td>
                                        <?php if ($r['status'] === 'present'): ?>
                                            <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-check me-1"></i> Present</span>
                                        <?php elseif ($r['status'] === 'absent'): ?>
                                            <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-xmark me-1"></i> Absent</span>
                                        <?php elseif ($r['status'] === 'late'): ?>
                                            <span class="badge bg-warning text-dark px-3 py-2"><i class="fa-solid fa-clock me-1"></i> Late</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($r['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($r['remarks'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
