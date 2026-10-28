<?php
$page_title = 'My Classes & Students';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_id = $stmt->fetchColumn() ?: 0;

// Fetch classes assigned
$stmt = $pdo->prepare("SELECT DISTINCT c.*, d.name as dept_name,
                              (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'active') as student_count 
                       FROM classes c 
                       LEFT JOIN departments d ON c.department_id = d.id 
                       WHERE c.class_teacher_id = ? OR c.id IN (SELECT class_id FROM subjects WHERE teacher_id = ?) 
                       ORDER BY c.numeric_level ASC");
$stmt->execute([$teacher_id, $teacher_id]);
$assigned_classes = $stmt->fetchAll();

$view_class_id = intval($_GET['class_id'] ?? ($assigned_classes[0]['id'] ?? 0));
$students_in_class = [];
if ($view_class_id > 0) {
    $st_stmt = $pdo->prepare("SELECT s.*, u.full_name, u.email, u.phone 
                              FROM students s 
                              JOIN users u ON s.user_id = u.id 
                              WHERE s.class_id = ? AND s.status = 'active' 
                              ORDER BY s.roll_no ASC");
    $st_stmt->execute([$view_class_id]);
    $students_in_class = $st_stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-users-rectangle text-primary me-2"></i> Assigned Classes & Student Roster</h4>
                <p class="text-muted small mb-0">Browse the student cohorts enrolled in your assigned sections.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="list-group shadow-sm border-0">
                    <?php foreach ($assigned_classes as $ac): ?>
                        <a href="?class_id=<?php echo $ac['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 <?php echo $view_class_id == $ac['id'] ? 'active bg-primary border-primary text-white' : ''; ?>">
                            <div>
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($ac['name'] . ' (' . $ac['section'] . ')'); ?></h6>
                                <small class="<?php echo $view_class_id == $ac['id'] ? 'text-white text-opacity-75' : 'text-muted'; ?>">Dept: <?php echo htmlspecialchars($ac['dept_name'] ?? 'General'); ?></small>
                            </div>
                            <span class="badge <?php echo $view_class_id == $ac['id'] ? 'bg-light text-primary' : 'bg-primary bg-opacity-10 text-primary'; ?> rounded-pill">
                                <?php echo $ac['student_count']; ?> Students
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">Student Roster</h6>
                        <a href="<?php echo BASE_URL; ?>/teacher/attendance.php?class_id=<?php echo $view_class_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-clipboard-user me-1"></i> Mark Attendance
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Roll</th>
                                    <th>Student Name</th>
                                    <th>Admission No</th>
                                    <th>Contact</th>
                                    <th>Parent Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students_in_class)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No students found in this class.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students_in_class as $stu): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($stu['roll_no']); ?></strong></td>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($stu['full_name']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($stu['admission_no']); ?></span></td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($stu['email']); ?></td>
                                            <td class="small text-muted"><?php echo htmlspecialchars($stu['parent_phone']); ?></td>
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
