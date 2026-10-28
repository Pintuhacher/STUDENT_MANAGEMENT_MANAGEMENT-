<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';

// Fetch active classes for dropdown
$classes = [];
try {
    $classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $class_id = intval($_POST['class_id'] ?? 0);
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? 'Male';
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($class_id)) {
        $error = 'Please fill out all required fields marked with an asterisk (*).';
    } else {
        // Check uniqueness
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email is already registered. Please choose another or login.';
        } else {
            try {
                $pdo->beginTransaction();

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $u_stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, full_name, phone, status) VALUES (?, ?, 'student', ?, ?, ?, 'pending')");
                $u_stmt->execute([$username, $hash, $email, $full_name, $phone]);
                $user_id = $pdo->lastInsertId();

                $admission_no = 'ADM-' . date('Y') . '-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);
                $roll_no = 'R-' . str_pad($user_id, 3, '0', STR_PAD_LEFT);

                $s_stmt = $pdo->prepare("INSERT INTO students (user_id, admission_no, roll_no, class_id, dob, gender, father_name, mother_name, parent_phone, address, admission_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'pending')");
                $s_stmt->execute([$user_id, $admission_no, $roll_no, $class_id, $dob ?: '2008-01-01', $gender, $father_name ?: 'Guardian', $mother_name ?: 'Guardian', $parent_phone ?: $phone, $address ?: 'Address', ]);

                log_activity($pdo, $user_id, 'Student Self-Registration', "Submitted application for admission no: $admission_no");

                $pdo->commit();
                $success = 'Registration submitted successfully! Your account is in the Pending Approval queue. Once the administrator approves it, you can log in with your credentials.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Failed to submit registration: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - EduDash SMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Student Self-Registration Portal</h5>
                    <p class="small mb-0 opacity-75">Submit your admission application for Admin approval</p>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <div class="mt-3">
                                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary btn-sm">Return to Login Portal</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <?php echo csrf_field(); ?>
                            <h6 class="fw-bold text-primary mb-3">1. Account Credentials</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Full Name *</label>
                                    <input type="text" name="full_name" class="form-control" required placeholder="e.g. Rahul Sen">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Desired Username *</label>
                                    <input type="text" name="username" class="form-control" required placeholder="e.g. rahul2026">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required placeholder="rahul@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="10-digit mobile number">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Password *</label>
                                    <input type="password" name="password" class="form-control" required placeholder="Secure account password">
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3">2. Academic & Personal Details</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Applying For Class *</label>
                                    <select name="class_id" class="form-select" required>
                                        <option value="">-- Choose Class & Section --</option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?php echo $c['id']; ?>">
                                                <?php echo htmlspecialchars($c['name'] . ' (Sec ' . $c['section'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control" value="2009-01-01">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Father's Name</label>
                                    <input type="text" name="father_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Mother's Name</label>
                                    <input type="text" name="mother_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Parent / Guardian Phone</label>
                                    <input type="text" name="parent_phone" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Residential Address</label>
                                    <textarea name="address" class="form-control" rows="1" placeholder="Address"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary">Already registered? Log In</a>
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">Submit Application <i class="fa-solid fa-arrow-right ms-1"></i></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
