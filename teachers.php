<?php
$page_title = 'Teacher Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$action = $_GET['action'] ?? 'list';
$teacher_id = intval($_GET['id'] ?? 0);

// Fetch departments
$departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

// Handle Delete
if ($action === 'delete' && $teacher_id > 0) {
    validate_csrf();
    $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $u_id = $stmt->fetchColumn();
    if ($u_id) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$u_id]);
        log_activity($pdo, $_SESSION['user_id'], 'Delete Teacher', "Deleted teacher ID: $teacher_id (User: $u_id)");
        set_flash('success', 'Teacher profile removed successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/teachers.php');
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    validate_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $department_id = intval($_POST['department_id'] ?? 0) ?: null;
    $qualification = trim($_POST['qualification'] ?? '');
    $joining_date = $_POST['joining_date'] ?? date('Y-m-d');
    $address = trim($_POST['address'] ?? '');

    if ($action === 'add') {
        $password = $_POST['password'] ?? 'teacher123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->beginTransaction();
        try {
            $u_stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name, phone, status) VALUES (?, ?, 'teacher', ?, ?, ?, 'active')");
            $u_stmt->execute([$username, $hash, $email, $full_name, $phone]);
            $user_id = $pdo->lastInsertId();

            $t_stmt = $pdo->prepare("INSERT INTO teachers (user_id, employee_id, department_id, qualification, joining_date, address) VALUES (?, ?, ?, ?, ?, ?)");
            $t_stmt->execute([$user_id, $employee_id, $department_id, $qualification, $joining_date, $address]);

            $pdo->commit();
            log_activity($pdo, $_SESSION['user_id'], 'Add Teacher', "Enrolled teacher $full_name ($employee_id)");
            set_flash('success', 'Teacher added successfully!');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error adding teacher: ' . $e->getMessage());
        }
    } elseif ($action === 'edit' && $teacher_id > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM teachers WHERE id = ?");
            $stmt->execute([$teacher_id]);
            $user_id = $stmt->fetchColumn();

            $u_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?";
            $u_params = [$full_name, $email, $phone];
            if (!empty($_POST['password'])) {
                $u_sql .= ", password = ?";
                $u_params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            $u_sql .= " WHERE id = ?";
            $u_params[] = $user_id;
            $pdo->prepare($u_sql)->execute($u_params);

            $t_stmt = $pdo->prepare("UPDATE teachers SET employee_id = ?, department_id = ?, qualification = ?, joining_date = ?, address = ? WHERE id = ?");
            $t_stmt->execute([$employee_id, $department_id, $qualification, $joining_date, $address, $teacher_id]);

            $pdo->commit();
            log_activity($pdo, $_SESSION['user_id'], 'Edit Teacher', "Updated teacher ID: $teacher_id");
            set_flash('success', 'Teacher record updated successfully.');
            header('Location: ' . BASE_URL . '/admin/teachers.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error updating teacher: ' . $e->getMessage());
        }
    }
}

// Edit Record Fetch
$teacher_data = null;
if ($action === 'edit' && $teacher_id > 0) {
    $stmt = $pdo->prepare("SELECT t.*, u.username, u.full_name, u.email, u.phone FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher_data = $stmt->fetch();
}

// Fetch Teachers List
$teachers_list = $pdo->query("SELECT t.*, u.username, u.full_name, u.email, u.phone, d.name as dept_name 
                             FROM teachers t 
                             JOIN users u ON t.user_id = u.id 
                             LEFT JOIN departments d ON t.department_id = d.id 
                             ORDER BY t.id DESC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <?php if ($action === 'list'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fa-solid fa-chalkboard-user text-primary me-2"></i> Faculty & Teacher Management</h4>
                    <p class="text-muted small mb-0">Manage teachers, assigned academic departments, and employee credentials.</p>
                </div>
                <a href="?action=add" class="btn btn-primary btn-sm fw-semibold">
                    <i class="fa-solid fa-plus me-1"></i> Add New Teacher
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Emp ID</th>
                                <th>Faculty Name</th>
                                <th>Department</th>
                                <th>Qualification</th>
                                <th>Contact</th>
                                <th>Joining Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($teachers_list)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No faculty members found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers_list as $t): ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($t['employee_id']); ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($t['full_name']); ?></div>
                                            <small class="text-muted">@<?php echo htmlspecialchars($t['username']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <?php echo htmlspecialchars($t['dept_name'] ?? 'General'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($t['qualification']); ?></td>
                                        <td>
                                            <div class="small"><?php echo htmlspecialchars($t['email']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($t['phone'] ?: '-'); ?></div>
                                        </td>
                                        <td><?php echo format_date($t['joining_date']); ?></td>
                                        <td class="text-end">
                                            <a href="?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen-to-square"></i></a>
                                            <form method="POST" action="?action=delete&id=<?php echo $t['id']; ?>" class="d-inline" onsubmit="return confirm('Permanently remove this teacher?');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'add' || ($action === 'edit' && $teacher_data)): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">
                                <?php echo $action === 'add' ? 'Add New Faculty Member' : 'Edit Teacher: ' . htmlspecialchars($teacher_data['full_name']); ?>
                            </h5>
                            <a href="<?php echo BASE_URL; ?>/admin/teachers.php" class="btn btn-sm btn-outline-secondary">Back to List</a>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <?php echo csrf_field(); ?>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($teacher_data['full_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Username *</label>
                                        <input type="text" name="username" class="form-control" required <?php echo $action === 'edit' ? 'readonly' : ''; ?> value="<?php echo htmlspecialchars($teacher_data['username'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Email *</label>
                                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($teacher_data['email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Password <?php echo $action === 'edit' ? '(Leave blank to retain)' : '*'; ?></label>
                                        <input type="password" name="password" class="form-control" <?php echo $action === 'add' ? 'required' : ''; ?> placeholder="<?php echo $action === 'add' ? 'Default teacher password' : '••••••••'; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Employee ID *</label>
                                        <input type="text" name="employee_id" class="form-control" required value="<?php echo htmlspecialchars($teacher_data['employee_id'] ?? ('TCH-' . date('Y') . '-' . rand(100, 999))); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Department</label>
                                        <select name="department_id" class="form-select">
                                            <option value="">-- Select Department --</option>
                                            <?php foreach ($departments as $d): ?>
                                                <option value="<?php echo $d['id']; ?>" <?php echo ($teacher_data['department_id'] ?? 0) == $d['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($d['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Educational Qualification</label>
                                        <input type="text" name="qualification" class="form-control" placeholder="e.g. M.Sc, B.Ed, Ph.D" value="<?php echo htmlspecialchars($teacher_data['qualification'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Joining Date</label>
                                        <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($teacher_data['joining_date'] ?? date('Y-m-d')); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Phone Number</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($teacher_data['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Address</label>
                                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($teacher_data['address'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?php echo BASE_URL; ?>/admin/teachers.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary fw-semibold px-4">Save Teacher</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
