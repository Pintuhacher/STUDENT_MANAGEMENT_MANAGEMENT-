<?php
$page_title = 'System Settings';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $keys = ['institution_name', 'institution_email', 'institution_phone', 'institution_address', 'current_session', 'currency_symbol'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = trim($_POST[$k]);
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$k, $val, $val]);
        }
    }
    log_activity($pdo, $_SESSION['user_id'], 'Update Settings', 'Institutional settings modified.');
    set_flash('success', 'System settings saved successfully!');
    header('Location: ' . BASE_URL . '/admin/settings.php');
    exit;
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="mb-4">
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-gear text-primary me-2"></i> System Configuration</h4>
            <p class="text-muted small mb-0">Configure institution branding, contact details, and academic session parameters.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Institution / Academy Name *</label>
                            <input type="text" name="institution_name" class="form-control" required value="<?php echo htmlspecialchars(get_setting($pdo, 'institution_name', 'EduDash International Academy')); ?>">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Official Email</label>
                                <input type="email" name="institution_email" class="form-control" value="<?php echo htmlspecialchars(get_setting($pdo, 'institution_email', 'admin@edudash.edu')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact Phone</label>
                                <input type="text" name="institution_phone" class="form-control" value="<?php echo htmlspecialchars(get_setting($pdo, 'institution_phone', '+91 98765 43210')); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Campus Address</label>
                            <textarea name="institution_address" class="form-control" rows="2"><?php echo htmlspecialchars(get_setting($pdo, 'institution_address', 'Knowledge Campus, West Bengal, India')); ?></textarea>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Current Academic Session</label>
                                <input type="text" name="current_session" class="form-control" value="<?php echo htmlspecialchars(get_setting($pdo, 'current_session', '2026-2027')); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars(get_setting($pdo, 'currency_symbol', '₹')); ?>">
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="fa-solid fa-save me-1"></i> Save Configuration</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
