<?php
$page_title = 'Student Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$action = $_GET['action'] ?? 'list';
$student_id = intval($_GET['id'] ?? 0);

// Fetch classes
$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();

// Handle Delete
if ($action === 'delete' && $student_id > 0) {
    validate_csrf();
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $u_id = $stmt->fetchColumn();
    if ($u_id) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$u_id]);
        log_activity($pdo, $_SESSION['user_id'], 'Delete Student', "Deleted student ID: $student_id (User ID: $u_id)");
        set_flash('success', 'Student record deleted successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/students.php');
    exit;
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    validate_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $class_id = intval($_POST['class_id'] ?? 0);
    $dob = $_POST['dob'] ?? '2008-01-01';
    $gender = $_POST['gender'] ?? 'Male';
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($action === 'add') {
        $password = $_POST['password'] ?? 'student123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $pdo->beginTransaction();
        try {
            $u_stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name, phone, status) VALUES (?, ?, 'student', ?, ?, ?, ?)");
            $u_stmt->execute([$username, $hash, $email, $full_name, $phone, $status]);
            $user_id = $pdo->lastInsertId();

            $admission_no = 'ADM-' . date('Y') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
            $s_stmt = $pdo->prepare("INSERT INTO students (user_id, admission_no, roll_no, class_id, dob, gender, father_name, mother_name, parent_phone, address, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)");
            $s_stmt->execute([$user_id, $admission_no, $roll_no, $class_id, $dob, $gender, $father_name, $mother_name, $parent_phone, $address, $status]);
            
            $pdo->commit();
            log_activity($pdo, $_SESSION['user_id'], 'Add Student', "Created student $full_name ($admission_no)");
            set_flash('success', 'Student enrolled successfully!');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error enrolling student: ' . $e->getMessage());
        }
    } elseif ($action === 'edit' && $student_id > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $user_id = $stmt->fetchColumn();

            $u_sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, status = ?";
            $u_params = [$full_name, $email, $phone, $status];
            if (!empty($_POST['password'])) {
                $u_sql .= ", password = ?";
                $u_params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            $u_sql .= " WHERE id = ?";
            $u_params[] = $user_id;
            $pdo->prepare($u_sql)->execute($u_params);

            $s_stmt = $pdo->prepare("UPDATE students SET roll_no = ?, class_id = ?, dob = ?, gender = ?, father_name = ?, mother_name = ?, parent_phone = ?, address = ?, status = ? WHERE id = ?");
            $s_stmt->execute([$roll_no, $class_id, $dob, $gender, $father_name, $mother_name, $parent_phone, $address, $status, $student_id]);

            $pdo->commit();
            log_activity($pdo, $_SESSION['user_id'], 'Edit Student', "Updated student ID: $student_id");
            set_flash('success', 'Student details updated successfully.');
            header('Location: ' . BASE_URL . '/admin/students.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Error updating student: ' . $e->getMessage());
        }
    }
}

// Student Edit Data
$student_data = null;
if ($action === 'edit' && $student_id > 0) {
    $stmt = $pdo->prepare("SELECT s.*, u.username, u.full_name, u.email, u.phone, u.status as user_status FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student_data = $stmt->fetch();
}

// Search and Filter
$filter_class = intval($_GET['class_id'] ?? 0);
$filter_search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';

$query = "SELECT s.*, u.username, u.full_name, u.email, u.phone, c.name as class_name, c.section 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          LEFT JOIN classes c ON s.class_id = c.id 
          WHERE 1=1";
$params = [];

if ($filter_class > 0) {
    $query .= " AND s.class_id = ?";
    $params[] = $filter_class;
}
if (!empty($filter_status)) {
    $query .= " AND s.status = ?";
    $params[] = $filter_status;
}
if (!empty($filter_search)) {
    $query .= " AND (u.full_name LIKE ? OR s.admission_no LIKE ? OR s.roll_no LIKE ? OR u.email LIKE ?)";
    $like = "%$filter_search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$query .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students_list = $stmt->fetchAll();

// CSV Export Check
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Admission No', 'Roll No', 'Full Name', 'Class', 'Section', 'Email', 'Phone', 'Father Name', 'Parent Phone', 'Status']);
    foreach ($students_list as $row) {
        fputcsv($out, [
            $row['id'], $row['admission_no'], $row['roll_no'], $row['full_name'],
            $row['class_name'] ?? 'N/A', $row['section'] ?? '', $row['email'],
            $row['phone'], $row['father_name'], $row['parent_phone'], $row['status']
        ]);
    }
    exit;
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <?php if ($action === 'list'): ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-graduate text-primary me-2"></i> Student Directory</h4>
                    <p class="text-muted small mb-0">Manage all registered students, enrollment details, and academic profiles.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?export=csv<?php echo $filter_class ? '&class_id='.$filter_class : ''; ?><?php echo $filter_search ? '&search='.urlencode($filter_search) : ''; ?>" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                    </a>
                    <a href="?action=add" class="btn btn-primary btn-sm fw-semibold">
                        <i class="fa-solid fa-plus me-1"></i> Add New Student
                    </a>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card border-0 shadow-sm p-3 mb-4">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search name, admission no, roll..." value="<?php echo htmlspecialchars($filter_search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="class_id" class="form-select form-select-sm">
                            <option value="">-- All Classes --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $filter_class == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">-- All Statuses --</option>
                            <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="graduated" <?php echo $filter_status == 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                        <a href="<?php echo BASE_URL; ?>/admin/students.php" class="btn btn-light btn-sm"><i class="fa-solid fa-rotate-left"></i></a>
                    </div>
                </form>
            </div>

            <!-- Student Table -->
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Admission / Roll</th>
                                <th>Student Name</th>
                                <th>Class & Sec</th>
                                <th>Parent Details</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No student records found matching your filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students_list as $s): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['admission_no']); ?></span>
                                            <div class="small text-muted mt-1">Roll: <?php echo htmlspecialchars($s['roll_no']); ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($s['email']); ?> | <?php echo htmlspecialchars($s['phone'] ?: '-'); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                <?php echo htmlspecialchars(($s['class_name'] ?? 'Class') . ' ' . ($s['section'] ?? '')); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold"><?php echo htmlspecialchars($s['father_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($s['parent_phone']); ?></div>
                                        </td>
                                        <td>
                                            <?php if ($s['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif ($s['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo ucfirst($s['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="?action=edit&id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Student"><i class="fa-solid fa-pen-to-square"></i></a>
                                            <form method="POST" action="?action=delete&id=<?php echo $s['id']; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this student record?');">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'add' || ($action === 'edit' && $student_data)): ?>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">
                                <i class="fa-solid <?php echo $action === 'add' ? 'fa-user-plus' : 'fa-user-pen'; ?> text-primary me-2"></i>
                                <?php echo $action === 'add' ? 'Enroll New Student' : 'Edit Student Profile: ' . htmlspecialchars($student_data['full_name']); ?>
                            </h5>
                            <a href="<?php echo BASE_URL; ?>/admin/students.php" class="btn btn-sm btn-outline-secondary">Back to List</a>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <?php echo csrf_field(); ?>
                                <h6 class="fw-bold text-primary mb-3">1. Login Credentials & System Info</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($student_data['full_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Username *</label>
                                        <input type="text" name="username" class="form-control" required <?php echo $action === 'edit' ? 'readonly' : ''; ?> value="<?php echo htmlspecialchars($student_data['username'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Email *</label>
                                        <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($student_data['email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Password <?php echo $action === 'edit' ? '(Leave blank to retain current)' : '*'; ?></label>
                                        <input type="password" name="password" class="form-control" <?php echo $action === 'add' ? 'required' : ''; ?> placeholder="<?php echo $action === 'add' ? 'Default student password' : '••••••••'; ?>">
                                    </div>
                                </div>

                                <h6 class="fw-bold text-primary mb-3">2. Academic Information</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Assigned Class *</label>
                                        <select name="class_id" class="form-select" required>
                                            <option value="">-- Choose Class --</option>
                                            <?php foreach ($classes as $c): ?>
                                                <option value="<?php echo $c['id']; ?>" <?php echo ($student_data['class_id'] ?? 0) == $c['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($c['name'] . ' (Sec ' . $c['section'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Roll Number *</label>
                                        <input type="text" name="roll_no" class="form-control" required value="<?php echo htmlspecialchars($student_data['roll_no'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Account Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?php echo ($student_data['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="pending" <?php echo ($student_data['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="inactive" <?php echo ($student_data['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="graduated" <?php echo ($student_data['status'] ?? '') == 'graduated' ? 'selected' : ''; ?>>Graduated</option>
                                        </select>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-primary mb-3">3. Personal & Guardian Details</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Date of Birth</label>
                                        <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student_data['dob'] ?? '2008-01-01'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Gender</label>
                                        <select name="gender" class="form-select">
                                            <option value="Male" <?php echo ($student_data['gender'] ?? 'Male') == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($student_data['gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($student_data['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Student Phone</label>
                                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($student_data['phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Father's Name</label>
                                        <input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($student_data['father_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Mother's Name</label>
                                        <input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($student_data['mother_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">Parent Contact Phone</label>
                                        <input type="text" name="parent_phone" class="form-control" value="<?php echo htmlspecialchars($student_data['parent_phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-semibold">Residential Address</label>
                                        <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($student_data['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="<?php echo BASE_URL; ?>/admin/students.php" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                                        <i class="fa-solid fa-save me-1"></i> Save Student Record
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
