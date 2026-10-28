<?php
$page_title = 'Subject Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
$teachers = $pdo->query("SELECT t.id, u.full_name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY u.full_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    validate_csrf();
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $class_id = intval($_POST['class_id'] ?? 0);
    $teacher_id = intval($_POST['teacher_id'] ?? 0) ?: null;
    $type = $_POST['type'] ?? 'Theory';
    $max_marks = intval($_POST['max_marks'] ?? 100);
    $pass_marks = intval($_POST['pass_marks'] ?? 35);

    if (!empty($name) && !empty($code) && $class_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO subjects (name, code, class_id, teacher_id, type, max_marks, pass_marks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $code, $class_id, $teacher_id, $type, $max_marks, $pass_marks]);
        log_activity($pdo, $_SESSION['user_id'], 'Add Subject', "Added subject $name ($code)");
        set_flash('success', 'Subject created and assigned successfully.');
        header('Location: ' . BASE_URL . '/admin/subjects.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    validate_csrf();
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
    log_activity($pdo, $_SESSION['user_id'], 'Delete Subject', "Deleted subject ID: $id");
    set_flash('success', 'Subject deleted.');
    header('Location: ' . BASE_URL . '/admin/subjects.php');
    exit;
}

$subjects = $pdo->query("SELECT s.*, c.name as class_name, c.section, u.full_name as teacher_name 
                         FROM subjects s 
                         JOIN classes c ON s.class_id = c.id 
                         LEFT JOIN teachers t ON s.teacher_id = t.id 
                         LEFT JOIN users u ON t.user_id = u.id 
                         ORDER BY c.name ASC, s.name ASC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-book-open text-primary me-2"></i> Subject Curriculum</h4>
                <p class="text-muted small mb-0">Create subjects, configure theory/practical types, pass marks, and assign teachers.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="fa-solid fa-plus me-1"></i> Add Subject
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th>Class & Section</th>
                            <th>Subject Teacher</th>
                            <th>Type</th>
                            <th>Max / Pass Marks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s['code']); ?></span></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($s['class_name'] . ' ' . $s['section']); ?></span></td>
                                <td><i class="fa-solid fa-chalkboard-user text-muted me-1"></i> <?php echo htmlspecialchars($s['teacher_name'] ?? 'Unassigned'); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo $s['type']; ?></span></td>
                                <td><?php echo $s['max_marks']; ?> / <?php echo $s['pass_marks']; ?></td>
                                <td class="text-end">
                                    <form method="GET" action="" class="d-inline" onsubmit="return confirm('Delete this subject?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="addSubjectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Add Academic Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Subject Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Advanced Physics">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Subject Code *</label>
                                    <input type="text" name="code" class="form-control" required placeholder="e.g. PHY-102">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Type</label>
                                    <select name="type" class="form-select">
                                        <option value="Theory">Theory</option>
                                        <option value="Practical">Practical</option>
                                        <option value="Combined">Combined</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Class Assigned *</label>
                                <select name="class_id" class="form-select" required>
                                    <option value="">-- Choose Class --</option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Subject Teacher</label>
                                <select name="teacher_id" class="form-select">
                                    <option value="">-- Assign Later --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Max Marks</label>
                                    <input type="number" name="max_marks" class="form-control" value="100">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Pass Marks</label>
                                    <input type="number" name="pass_marks" class="form-control" value="35">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_subject" class="btn btn-primary btn-sm fw-semibold">Save Subject</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
