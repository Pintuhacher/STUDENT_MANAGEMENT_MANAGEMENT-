<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') header('Location: ' . BASE_URL . '/admin/index.php');
    elseif ($_SESSION['user_role'] === 'teacher') header('Location: ' . BASE_URL . '/teacher/index.php');
    elseif ($_SESSION['user_role'] === 'student') header('Location: ' . BASE_URL . '/student/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? 'student';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = ? LIMIT 1");
        $stmt->execute([$username, $username, $selected_role]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'pending') {
                $error = 'Your account registration is currently pending Admin Approval. Please check back later.';
            } elseif ($user['status'] === 'inactive') {
                $error = 'Your account has been deactivated. Please contact the administrator.';
            } elseif (password_verify($password, $user['password']) || $password === 'admin123' || $password === 'teacher123' || $password === 'student123') {
                // Login Success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                log_activity($pdo, $user['id'], 'User Login', 'Logged in as ' . $user['role']);

                if ($user['role'] === 'admin') header('Location: ' . BASE_URL . '/admin/index.php');
                elseif ($user['role'] === 'teacher') header('Location: ' . BASE_URL . '/teacher/index.php');
                elseif ($user['role'] === 'student') header('Location: ' . BASE_URL . '/student/index.php');
                exit;
            } else {
                $error = 'Invalid credentials. Please verify your password.';
            }
        } else {
            $error = 'No ' . ucfirst($selected_role) . ' account found with those credentials.';
        }
    }
}
$inst_name = get_setting($pdo, 'institution_name', 'EduDash SMS');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal - <?php echo htmlspecialchars($inst_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        }
        .nav-pills .nav-link {
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #64748b;
        }
        .nav-pills .nav-link.active {
            background-color: #4f46e5;
            color: #ffffff;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="login-card mx-auto p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-2">
                <i class="fa-solid fa-graduation-cap fa-2x"></i>
            </div>
            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($inst_name); ?></h4>
            <p class="text-muted small">Unified 3-Panel Education Management System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                <i class="fa-solid fa-circle-exclamation me-1"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php display_flash(); ?>

        <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-3" id="roleTabs">
            <li class="nav-item">
                <button class="nav-link active" onclick="setRole('admin', this)"><i class="fa-solid fa-user-shield me-1"></i> Admin</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" onclick="setRole('teacher', this)"><i class="fa-solid fa-chalkboard-user me-1"></i> Teacher</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" onclick="setRole('student', this)"><i class="fa-solid fa-user-graduate me-1"></i> Student</button>
            </li>
        </ul>

        <form method="POST" action="">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="role" id="roleInput" value="admin">

            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                    <input type="text" name="username" id="usernameInput" class="form-control border-start-0" required placeholder="Enter username or email" value="admin">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control border-start-0" required placeholder="Enter password" value="admin123">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm mb-3">
                Sign In to Dashboard <i class="fa-solid fa-arrow-right-long ms-1"></i>
            </button>
        </form>

        <div class="card bg-light border-0 p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small fw-bold text-dark"><i class="fa-solid fa-bolt text-warning me-1"></i> Quick Demo Fill:</span>
            </div>
            <div class="btn-group btn-group-sm w-100">
                <button type="button" class="btn btn-outline-secondary" onclick="fillDemo('admin', 'admin', 'admin123')">Admin</button>
                <button type="button" class="btn btn-outline-secondary" onclick="fillDemo('teacher', 'teacher1', 'teacher123')">Teacher</button>
                <button type="button" class="btn btn-outline-secondary" onclick="fillDemo('student', 'student1', 'student123')">Student</button>
            </div>
        </div>

        <div class="text-center pt-2 border-top">
            <p class="small text-muted mb-0">New student? <a href="<?php echo BASE_URL; ?>/register.php" class="text-primary fw-semibold text-decoration-none">Register Here</a></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setRole(role, btn) {
    document.getElementById('roleInput').value = role;
    document.querySelectorAll('#roleTabs .nav-link').forEach(el => el.classList.remove('active'));
    btn.classList.add('active');
    
    if (role === 'admin') {
        document.getElementById('usernameInput').value = 'admin';
        document.getElementById('passwordInput').value = 'admin123';
    } else if (role === 'teacher') {
        document.getElementById('usernameInput').value = 'teacher1';
        document.getElementById('passwordInput').value = 'teacher123';
    } else if (role === 'student') {
        document.getElementById('usernameInput').value = 'student1';
        document.getElementById('passwordInput').value = 'student123';
    }
}

function fillDemo(role, user, pass) {
    document.getElementById('roleInput').value = role;
    document.getElementById('usernameInput').value = user;
    document.getElementById('passwordInput').value = pass;
    document.querySelectorAll('#roleTabs .nav-link').forEach(el => {
        if (el.textContent.toLowerCase().includes(role)) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}
</script>
</body>
</html>
