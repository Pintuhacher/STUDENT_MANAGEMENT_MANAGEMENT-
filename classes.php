<?php
$page_title = 'Class Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

// Add Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    validate_csrf();
    $name = trim($_POST['name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0) ?: null;
    $class_teacher_id = intval($_POST['class_teacher_id'] ?? 0) ?: null;
    $numeric_level = intval($_POST['numeric_level'] ?? 1);

    if (!empty($name) && !empty($section)) {
        $stmt = $pdo->prepare("INSERT INTO classes (name, section, department_id, class_teacher_id, numeric_level) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $section, $department_id, $class_teacher_id, $numeric_level]);
        log_activity($pdo, $_SESSION['user_id'], 'Create Class', "Created class: $name ($section)");
        set_flash('success', 'Class created successfully.');
        header('Location: ' . BASE_URL . '/admin/classes.php');
        exit;
    }
}

// Delete Class
if (isset($_GET['delete'])) {
    validate_csrf();
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM classes WHERE id = ?")->execute([$id]);
    log_activity($pdo, $_SESSION['user_id'], 'Delete Class', "Deleted class ID: $id");
    set_flash('success', 'Class removed successfully.');
    header('Location: ' . BASE_URL . '/admin/classes.php');
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$teachers = $pdo->query("SELECT t.id, u.full_name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY u.full_name ASC")->fetchAll();
$classes = $pdo->query("SELECT c.*, d.name as dept_name, u.full_name as teacher_name, 
                              (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'active') as student_count 
                       FROM classes c 
                       LEFT JOIN departments d ON c.department_id = d.id 
                       LEFT JOIN teachers t ON c.class_teacher_id = t.id 
                       LEFT JOIN users u ON t.user_id = u.id 
                       ORDER BY c.numeric_level ASC, c.name ASC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-school text-primary me-2"></i> Classes & Sections</h4>
                <p class="text-muted small mb-0">Define academic grade levels, sections, and assign responsible class teachers.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addClassModal">
                <i class="fa-solid fa-plus me-1"></i> Create Class
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Class Level</th>
                            <th>Class Name</th>
                            <th>Section</th>
                            <th>Department</th>
                            <th>Class Teacher</th>
                            <th>Enrolled Students</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td><span class="badge bg-secondary">Level <?php echo $c['numeric_level']; ?></span></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['section']); ?></span></td>
                                <td><?php echo htmlspecialchars($c['dept_name'] ?? 'General'); ?></td>
                                <td><i class="fa-solid fa-chalkboard-user text-muted me-1"></i> <?php echo htmlspecialchars($c['teacher_name'] ?? 'Not Assigned'); ?></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary fs-6"><?php echo $c['student_count']; ?> Students</span></td>
                                <td class="text-end">
                                    <form method="GET" action="" class="d-inline" onsubmit="return confirm('Delete this class? Associated students will be unlinked.');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Class Modal -->
        <div class="modal fade" id="addClassModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Create New Class & Section</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Class Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Class 10 or Grade 11">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Section *</label>
                                    <input type="text" name="section" class="form-control" required placeholder="e.g. A, B, Science">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Numeric Order Level</label>
                                    <input type="number" name="numeric_level" class="form-control" value="10">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Department</label>
                                <select name="department_id" class="form-select">
                                    <option value="">-- None / General --</option>
                                    <?php foreach ($departments as $d): ?>
                                        <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Assign Class Teacher</label>
                                <select name="class_teacher_id" class="form-select">
                                    <option value="">-- Assign Later --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_class" class="btn btn-primary btn-sm fw-semibold">Create Class</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
