<?php
$page_title = 'Department Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dept'])) {
    validate_csrf();
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');

    if (!empty($name) && !empty($code)) {
        $stmt = $pdo->prepare("INSERT INTO departments (name, code, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $code, $desc]);
        log_activity($pdo, $_SESSION['user_id'], 'Add Department', "Added department $name ($code)");
        set_flash('success', 'Department created successfully.');
        header('Location: ' . BASE_URL . '/admin/departments.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    validate_csrf();
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
    log_activity($pdo, $_SESSION['user_id'], 'Delete Department', "Deleted department ID: $id");
    set_flash('success', 'Department removed.');
    header('Location: ' . BASE_URL . '/admin/departments.php');
    exit;
}

$departments = $pdo->query("SELECT d.*, 
                           (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.id) as teacher_count,
                           (SELECT COUNT(*) FROM classes c WHERE c.department_id = d.id) as class_count 
                           FROM departments d ORDER BY d.name ASC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-sitemap text-primary me-2"></i> Academic Departments</h4>
                <p class="text-muted small mb-0">Organize faculty, subjects, and curricula under institutional departments.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addDeptModal">
                <i class="fa-solid fa-plus me-1"></i> Add Department
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Faculty Members</th>
                            <th>Associated Classes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($d['code']); ?></span></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($d['name']); ?></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($d['description']); ?></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info"><?php echo $d['teacher_count']; ?> Faculty</span></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo $d['class_count']; ?> Classes</span></td>
                                <td class="text-end">
                                    <form method="GET" action="" class="d-inline" onsubmit="return confirm('Delete this department?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete" value="<?php echo $d['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="addDeptModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Create Academic Department</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Department Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Computer Science & Engineering">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Code / Abbreviation *</label>
                                <input type="text" name="code" class="form-control" required placeholder="e.g. CSE">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_dept" class="btn btn-primary btn-sm fw-semibold">Save Department</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
